@extends('admin.layouts.app')

@section('title', 'Detail Invoice #' . $invoice->invoice_number)

@section('content')
<div class="flex flex-col gap-6">

    {{-- HEADER & TOOLBAR --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.invoices.index') }}" class="btn-icon btn-secondary" title="Kembali">
                    <i class="material-icons text-lg">arrow_back</i>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="page-title text-xl font-bold tracking-tight">Invoice <span class="text-indigo-600">#{{ $invoice->invoice_number }}</span></h1>
                        
                        @php
                            $badgeClass = match($invoice->status) {
                                'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
                                'unpaid' => 'bg-rose-50 text-rose-600 border-rose-100',
                                'partially_paid' => 'bg-amber-50 text-amber-600 border-amber-100',
                                'paid' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                'cancelled' => 'bg-slate-200 text-slate-500 border-slate-300',
                                'overdue' => 'bg-purple-50 text-purple-600 border-purple-100',
                                default => 'bg-gray-100 text-gray-600'
                            };
                            $statusLabel = match($invoice->status) {
                                'draft' => 'Draft',
                                'unpaid' => 'Belum Lunas',
                                'partially_paid' => 'Bayar Sebagian',
                                'paid' => 'Lunas',
                                'cancelled' => 'Dibatalkan',
                                'overdue' => 'Jatuh Tempo',
                                default => ucfirst($invoice->status)
                            };
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-md text-xs font-bold uppercase border {{ $badgeClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                        <i class="material-icons text-xs">event</i> {{ $invoice->order_date->format('d F Y') }}
                        <span class="text-slate-300">|</span>
                        <i class="material-icons text-xs">schedule</i> Dibuat {{ $invoice->created_at->format('d M Y H:i') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="flex flex-wrap items-center gap-2">
            
            <a href="{{ route('admin.invoices.pdf', $invoice->invoice_id) }}" target="_blank" class="btn btn-secondary pl-3 pr-4" title="Cetak PDF">
                <i class="material-icons text-lg mr-1 text-slate-500">print</i> Cetak
            </a>

            @if($invoice->status === 'draft')
                <a href="{{ route('admin.invoices.edit', $invoice->invoice_id) }}" class="btn btn-secondary text-amber-600 border-amber-200 hover:bg-amber-50">
                    <i class="material-icons text-lg">edit</i> Edit
                </a>
                <button type="button" onclick="confirmDelete('{{ route('admin.invoices.destroy', $invoice->invoice_id) }}', 'Hapus Draft?', 'Data akan dihapus permanen.')" class="btn btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50">
                    <i class="material-icons text-lg">delete</i> Hapus
                </button>
                <button type="button" onclick="confirmAction('{{ route('admin.invoices.confirm', $invoice->invoice_id) }}', 'Konfirmasi Invoice?', 'Stok akan dikurangi dan jurnal tercatat.', 'success')" class="btn btn-primary bg-emerald-600 hover:bg-emerald-700 border-transparent text-white shadow-lg shadow-emerald-200">
                    <i class="material-icons text-lg">check_circle</i> Posting
                </button>
            @elseif($invoice->status !== 'cancelled')
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.outside="open = false" type="button" class="btn btn-secondary pl-3 pr-2 shadow-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                        <i class="material-icons text-lg mr-2">settings</i> Opsi <i class="material-icons text-lg ms-1">expand_more</i>
                    </button>
                    <div x-show="open" style="display: none;" class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 z-50 overflow-hidden origin-top-right">
                        <div class="py-1">
                            @if(in_array($invoice->status, ['unpaid', 'partially_paid', 'paid', 'overdue']))
                                <a href="{{ route('admin.sales-returns.create', ['sales_invoice_id' => $invoice->invoice_id]) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <i class="material-icons text-base text-orange-500">keyboard_return</i> Retur Penjualan
                                </a>
                                {{-- Link ke Halaman Pemilihan Adjustment --}}
                                <a href="{{ route('admin.invoice-adjustments.create', ['invoice_id' => $invoice->invoice_id]) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <i class="material-icons text-base text-indigo-500">tune</i> Buat Penyesuaian
                                </a>
                            @endif
                            <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                            @if($invoice->amount_paid == 0 && $invoice->returns->isEmpty())
                                <button type="button" onclick="confirmAction('{{ route('admin.invoices.cancel', $invoice->invoice_id) }}', 'Batalkan Invoice?', 'Stok akan dikembalikan dan jurnal dibalik.', 'danger')" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors text-left">
                                    <i class="material-icons text-base">block</i> Batalkan Invoice
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- SECTION 1: KARTU INFORMASI UTAMA --}}
    <div class="card p-0 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-slate-700">
            {{-- Kiri: Pelanggan --}}
            <div class="p-6">
                <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i class="material-icons text-sm">person</i> Pelanggan
                </h5>
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 flex items-center justify-center font-bold text-lg shrink-0">
                        {{ substr($invoice->client->client_name ?? 'U', 0, 1) }}
                    </div>
                    <div class="space-y-1">
                        <div class="text-base font-bold text-slate-800 dark:text-white">
                            {{ $invoice->client->client_name ?? 'Pelanggan Umum' }}
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $invoice->client->person_in_charge ?? '-' }} | {{ $invoice->client->phone_number ?? '-' }}
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $invoice->client->address ?? '-' }}
                        </div>
                        @if($invoice->client && $invoice->client->balance > 0)
                            <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-full border border-emerald-100">
                                <i class="material-icons text-[12px]">savings</i>
                                Deposit: Rp {{ number_format($invoice->client->balance, 0, ',', '.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Kanan: Detail --}}
            <div class="p-6">
                <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i class="material-icons text-sm">info</i> Detail
                </h5>
                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <span class="block text-xs text-slate-400 mb-0.5">Jatuh Tempo</span>
                        <span class="block text-sm font-semibold {{ $invoice->status != 'paid' && $invoice->due_date < now() ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200' }}">
                            {{ $invoice->due_date->format('d/m/Y') }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs text-slate-400 mb-0.5">Sales Person</span>
                        <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            {{ $invoice->sales->full_name ?? 'Admin' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 2: TABEL BARANG --}}
    <div class="card overflow-hidden">
        <div class="table-container border-0 shadow-none rounded-none">
            <table class="table-modern">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th width="40%">Deskripsi Item</th>
                        <th width="15%" class="text-center">Kuantitas</th>
                        <th width="20%" class="text-right">Harga Satuan</th>
                        <th width="25%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-700 dark:text-slate-200">{{ $item->product->product_name ?? 'Item Dihapus' }}</div>
                                <div class="text-xs text-slate-500">{{ $item->product->product_code ?? '-' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-md text-xs font-bold border border-slate-200 dark:border-slate-600">
                                    {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? '' }}
                                </span>
                            </td>
                            <td class="text-right font-mono text-slate-600 dark:text-slate-300">
                                Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-mono font-bold text-slate-800 dark:text-white">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- SUMMARY & CALCULATIONS --}}
        <div class="p-6 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-200 dark:border-slate-700">
            <div class="flex flex-col md:flex-row justify-between gap-8">
                <div class="w-full md:w-1/2">
                    @if($invoice->notes)
                        <div class="text-sm text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-700 p-4 rounded border border-slate-200 dark:border-slate-600">
                            <span class="font-bold block mb-1">Catatan:</span>
                            {{ $invoice->notes }}
                        </div>
                    @endif
                </div>

                <div class="w-full md:w-[400px] space-y-2">
                    {{-- TOTALS (Subtotal, Tax, Etc) --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="font-mono font-medium text-slate-700 dark:text-slate-200">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->discount_amount > 0)
                    <div class="flex justify-between text-sm text-rose-600">
                        <span>Diskon ({{ $invoice->discount_percentage }}%)</span>
                        <span class="font-mono">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @foreach($invoice->taxes as $tax)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">{{ $tax->name }} ({{ $tax->pivot->rate }}%)</span>
                        <span class="font-mono font-medium text-slate-700 dark:text-slate-200">+ Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                    @foreach($invoice->additionalCosts as $cost)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">{{ $cost->description }}</span>
                        <span class="font-mono font-medium text-slate-700 dark:text-slate-200">+ Rp {{ number_format($cost->amount, 0, ',', '.') }}</span>
                    </div>
                    @endforeach

                    <div class="border-t border-slate-200 dark:border-slate-600 my-2"></div>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-slate-800 dark:text-white">Grand Total</span>
                        <span class="text-xl font-bold text-indigo-600 font-mono">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                    </div>

                    @php
                        // LOGIKA HITUNG SISA TAGIHAN (REVISI FIX DOUBLE COUNTING)
                        // Hanya hitung adjustment manual (flag = true)
                        // Adjustment otomatis diabaikan karena total_amount sudah berubah
                        
                        $adjDebit = $invoice->adjustments->where('type', 'debit_note')->where('is_calculation_adjustment', true)->sum('amount');
                        $adjCredit = $invoice->adjustments->where('type', 'credit_note')->where('is_calculation_adjustment', true)->sum('amount');
                        $returPotong = $invoice->deductingReturns->sum('total_amount');
                        
                        $finalBill = ($invoice->total_amount + $adjDebit) - $adjCredit - $returPotong;
                        $sisaTagihan = round($finalBill - $invoice->amount_paid, 2);

                        $defaultTab = ($sisaTagihan > 0.01 && $invoice->status != 'cancelled' && $invoice->status != 'draft') ? 'input_payment' : 'history';
                    @endphp

                    @if($adjDebit > 0 || $adjCredit > 0 || $returPotong > 0)
                        <div class="pt-2 text-xs text-slate-400 space-y-1 border-t border-dashed border-slate-200 mt-2">
                            @if($adjDebit) <div class="flex justify-between"><span>+ Koreksi Manual (+)</span> <span>Rp {{ number_format($adjDebit, 0, ',', '.') }}</span></div> @endif
                            @if($adjCredit) <div class="flex justify-between"><span>- Koreksi Manual (-)</span> <span>Rp {{ number_format($adjCredit, 0, ',', '.') }}</span></div> @endif
                            @if($returPotong) <div class="flex justify-between"><span>- Retur Barang</span> <span>Rp {{ number_format($returPotong, 0, ',', '.') }}</span></div> @endif
                        </div>
                    @endif

                    <div class="flex justify-between items-center text-sm font-medium pt-2 mt-2">
                        <span class="text-emerald-600">Sudah Dibayar</span>
                        <span class="font-mono text-emerald-600">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                    </div>
                    
                    {{-- STATUS PEMBAYARAN --}}
                    @if($sisaTagihan > 0.01)
                        {{-- KURANG BAYAR --}}
                        <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded p-2 flex justify-between items-center mt-2">
                            <span class="text-xs font-bold text-rose-600 uppercase">Sisa Tagihan</span>
                            <span class="font-mono font-bold text-rose-600 text-lg">
                                Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                            </span>
                        </div>
                    
                    @elseif($sisaTagihan < -0.01)
                        {{-- LEBIH BAYAR (OVERPAID) --}}
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-lg p-3 mt-2" x-data="{ showRefundModal: false }">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-bold text-indigo-600 uppercase flex items-center gap-1">
                                    <i class="material-icons text-sm">info</i> Kelebihan Bayar
                                </span>
                                <span class="font-mono font-bold text-indigo-600 text-lg">
                                    Rp {{ number_format(abs($sisaTagihan), 0, ',', '.') }}
                                </span>
                            </div>
                            
                            {{-- LOGIKA REFUND: Hanya muncul jika SUDAH ADA PEMBAYARAN --}}
                            @if($invoice->amount_paid > 0)
                                <button @click="showRefundModal = !showRefundModal" type="button" class="w-full btn btn-sm bg-indigo-600 text-white hover:bg-indigo-700 border-transparent">
                                    Proses Refund Uang
                                </button>
                            @else
                                <p class="text-[10px] text-indigo-500 italic text-center">
                                    (Koreksi pada invoice yang belum dibayar. Tidak perlu refund.)
                                </p>
                            @endif
                    
                            <div x-show="showRefundModal" class="mt-3 pt-3 border-t border-indigo-200">
                                <form action="{{ route('admin.invoices.refund', $invoice->invoice_id) }}" method="POST">
                                    @csrf
                                    <div class="space-y-2">
                                        <div>
                                            <label class="text-[10px] font-bold text-indigo-600 uppercase">Sumber Dana</label>
                                            <select name="company_bank_account_id" class="form-select text-xs h-9 w-full" required>
                                                @foreach($companyBankAccounts as $bank)
                                                    <option value="{{ $bank->company_bank_account_id }}">{{ $bank->bank_name }} - {{ $bank->account_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-[10px] font-bold text-indigo-600 uppercase">Tanggal</label>
                                                <input type="date" name="refund_date" class="form-input text-xs h-9" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-bold text-indigo-600 uppercase">Nominal</label>
                                                <input type="number" name="refund_amount" class="form-input text-xs h-9" value="{{ abs($sisaTagihan) }}" max="{{ abs($sisaTagihan) }}" step="0.01" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="w-full btn btn-sm btn-primary">Konfirmasi Refund</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    
                    @else
                        {{-- LUNAS --}}
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded p-2 flex justify-between items-center mt-2">
                            <span class="text-xs font-bold text-emerald-600 uppercase">Status</span>
                            <span class="font-bold text-emerald-600 text-lg flex items-center gap-1">
                                <i class="material-icons text-base">check_circle</i> LUNAS
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 3: TABULASI --}}
    @if($invoice->status != 'draft')
    <div class="card overflow-hidden" x-data="{ activeTab: '{{ $defaultTab }}' }">
        
        <div class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <div class="px-6 flex gap-6 overflow-x-auto">
                @if($sisaTagihan > 0.01 && $invoice->status != 'cancelled')
                <button @click="activeTab = 'input_payment'" class="py-4 text-sm font-bold border-b-2 transition-all whitespace-nowrap flex items-center gap-2" :class="activeTab === 'input_payment' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    <i class="material-icons text-lg">add_card</i> Input Pembayaran
                </button>
                @endif
                <button @click="activeTab = 'history'" class="py-4 text-sm font-bold border-b-2 transition-all whitespace-nowrap flex items-center gap-2" :class="activeTab === 'history' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    <i class="material-icons text-lg">history</i> Riwayat Bayar
                </button>
                <button @click="activeTab = 'returns'" class="py-4 text-sm font-bold border-b-2 transition-all whitespace-nowrap flex items-center gap-2" :class="activeTab === 'returns' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    <i class="material-icons text-lg">keyboard_return</i> Retur
                    @if($invoice->returns->count()) <span class="ml-1 px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600 text-[10px]">{{ $invoice->returns->count() }}</span> @endif
                </button>
                <button @click="activeTab = 'adjustments'" class="py-4 text-sm font-bold border-b-2 transition-all whitespace-nowrap flex items-center gap-2" :class="activeTab === 'adjustments' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    <i class="material-icons text-lg">tune</i> Log Koreksi
                    @if($invoice->adjustments->count()) <span class="ml-1 px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600 text-[10px]">{{ $invoice->adjustments->count() }}</span> @endif
                </button>
            </div>
        </div>
        
        <div class="p-6">

            {{-- TAB 1: INPUT PEMBAYARAN --}}
            @if($sisaTagihan > 0.01 && $invoice->status != 'cancelled')
                <div x-show="activeTab === 'input_payment'" x-transition x-data="paymentForm()">
                    
                    @if ($errors->any())
                        <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded text-sm">
                            <strong>Gagal:</strong> {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        {{-- KIRI: OPSI PEMBAYARAN --}}
                        <div class="space-y-6">
                            
                            {{-- Checkbox Deposit --}}
                            @if($invoice->client->balance > 0)
                                <div class="p-4 bg-emerald-50/50 border border-emerald-100 rounded-lg">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" name="use_credit" x-model="useCredit" @change="updateCalculation()" class="form-check-input w-5 h-5 text-emerald-600">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700">Gunakan Saldo Deposit</span>
                                            <span class="text-xs text-slate-500">Saldo tersedia: <span class="font-bold text-emerald-600">{{ number_format($invoice->client->balance, 0, ',', '.') }}</span></span>
                                        </div>
                                    </label>
                                    
                                    <div x-show="useCredit" x-transition class="mt-3 ml-8 text-xs text-emerald-700 bg-emerald-100/50 p-2 rounded">
                                        Akan memotong: <b>- <span x-text="formatRupiah(creditAmount)"></span></b> dari saldo deposit.
                                    </div>
                                </div>
                            @endif

                            {{-- KARTU MIDTRANS --}}
                            @if($gatewayMethod)
                            <div class="p-5 border border-indigo-100 bg-indigo-50/30 rounded-xl relative overflow-hidden group hover:border-indigo-300 transition-all">
                                <div class="absolute top-0 right-0 p-2 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <i class="material-icons text-6xl text-indigo-600">qr_code_2</i>
                                </div>
                                <div class="relative z-10">
                                    <h4 class="font-bold text-indigo-700 text-sm mb-1 flex items-center gap-2">
                                        <i class="material-icons text-base">payments</i> Bayar Online
                                    </h4>
                                    <p class="text-xs text-indigo-600/80 mb-4 max-w-[80%]">
                                        QRIS, Virtual Account, E-Wallet. Verifikasi Otomatis.
                                    </p>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs font-bold text-slate-500 uppercase">Yang harus dibayar</div>
                                        <div class="text-lg font-bold text-indigo-700 font-mono" x-text="formatRupiah(balanceAfterPay)"></div>
                                    </div>
                                    
                                    <button type="button" 
                                            @click="payWithMidtrans"
                                            :disabled="isProcessing || balanceAfterPay <= 0"
                                            class="mt-3 w-full btn bg-indigo-600 hover:bg-indigo-700 text-white border-transparent shadow-md shadow-indigo-500/20 justify-center">
                                        <span x-show="!isProcessing">Bayar Sekarang</span>
                                        <span x-show="isProcessing" class="flex items-center">
                                            <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Memproses...
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="h-px bg-slate-200 flex-1"></div>
                                <span class="text-xs text-slate-400 font-bold uppercase">ATAU MANUAL</span>
                                <div class="h-px bg-slate-200 flex-1"></div>
                            </div>
                            @endif
                        </div>

                        {{-- KANAN: FORM MANUAL --}}
                        <div class="space-y-4">
                            <form action="{{ route('admin.payments.store', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="use_credit" :value="useCredit ? 1 : 0">

                                <div class="space-y-4">
                                    <h4 class="font-bold text-slate-700 text-sm border-b border-slate-200 pb-2 mb-4">Pencatatan Manual (Admin)</h4>

                                    <div>
                                        <label class="form-label label-required text-indigo-600 uppercase">Uang Diterima (Cash/Transfer)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-lg">Rp</span>
                                            <input type="text" class="form-input pl-10 pr-4 text-xl font-bold font-mono text-right autonumeric h-12" x-ref="amountInput" placeholder="0" :required="!useCredit"> 
                                            <input type="hidden" name="amount" :value="cashAmount">
                                        </div>
                                        <div class="mt-1 text-right">
                                            <button type="button" @click="setMaxAmount()" class="text-[10px] text-indigo-600 hover:underline font-bold">Isi Sisa Tagihan</button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label class="form-label label-required">TANGGAL BAYAR</label>
                                            <input type="date" name="payment_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label">METODE PEMBAYARAN</label>
                                        <div x-init="initMethodSelect($el)">
                                            <select name="payment_method_id" class="tom-select w-full">
                                                <option value="">Pilih Metode...</option>
                                                @foreach($paymentMethods as $method)
                                                    <option value="{{ $method->payment_method_id }}" data-config="{{ $method->internal_input_config }}">{{ $method->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div x-show="cashAmount > 0">
                                        <label class="form-label label-required">MASUK KE AKUN (KAS/BANK)</label>
                                        <div x-init="initBankSelect($el)">
                                            <select name="company_bank_account_id" class="tom-select w-full" :required="cashAmount > 0">
                                                <option value="">Pilih Akun...</option>
                                                @foreach($companyBankAccounts as $account)
                                                    <option value="{{ $account->company_bank_account_id }}">{{ $account->bank_name }} - {{ $account->account_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div x-show="requiresRef" class="animate-enter">
                                        <label class="form-label label-required">No. Referensi</label>
                                        <input type="text" name="reference_number" class="form-input" placeholder="No. Transaksi" :required="requiresRef">
                                    </div>

                                    <div x-show="requiresProof" class="animate-enter">
                                        <label class="form-label label-required">Bukti Transfer</label>
                                        <input type="file" name="proof_of_payment" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept=".jpg,.jpeg,.png,.pdf" :required="requiresProof">
                                    </div>

                                    <div>
                                        <label class="form-label">CATATAN</label>
                                        <textarea name="notes" class="form-textarea h-20" placeholder="Keterangan tambahan..."></textarea>
                                    </div>
                                    
                                    <div class="pt-2">
                                        <button type="submit" class="btn btn-primary w-full justify-center">
                                            <i class="material-icons mr-2">save</i> Simpan Manual
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 2: RIWAYAT --}}
            <div x-show="activeTab === 'history'" x-transition>
                @if($invoice->payments->isEmpty())
                    <p class="text-center text-slate-400 italic py-8">Belum ada data pembayaran.</p>
                @else
                    <div class="table-container">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Metode / Rincian</th>
                                    <th class="text-right">Jumlah</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments->sortByDesc('payment_date') as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="font-bold text-slate-700">{{ $payment->paymentMethod->name ?? 'Manual' }}</span>
                                            @if($payment->reference_number) <span class="text-xs text-slate-500 block">{{ $payment->reference_number }}</span> @endif
                                            @if(str_contains($payment->notes, 'Subsidi') || str_contains($payment->notes, 'Overpay'))
                                                <div class="mt-1"><span class="text-[10px] bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded border border-indigo-100 inline-flex items-center"><i class="material-icons text-[10px] mr-1">savings</i> Ada Deposit</span></div>
                                            @endif
                                            @if($payment->proof_of_payment_path)
                                                <a href="{{ asset('storage/'.$payment->proof_of_payment_path) }}" target="_blank" class="text-[10px] text-indigo-600 hover:underline flex items-center gap-1 mt-1"><i class="material-icons text-[10px]">attachment</i> Lihat Bukti</a>
                                            @endif
                                        </td>
                                        <td class="text-right font-mono font-bold text-emerald-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($payment->status == 'completed') <span class="badge badge-success">Diterima</span>
                                            @elseif($payment->status == 'pending_verification') <span class="badge badge-warning">Verifikasi</span>
                                            @else <span class="badge badge-danger">Gagal</span> @endif
                                        </td>
                                        <td class="text-center">
                                            @if($payment->status === 'pending_verification')
        {{-- Tombol Approval (Tetap Sama) --}}
        <div class="flex gap-1 justify-center">
            <button onclick="confirmAction('{{ route('admin.payment-clearance.sales.approve', $payment->payment_id) }}', 'Terima Pembayaran?', 'Dana akan masuk ke kas.', 'success')" class="text-emerald-600 hover:bg-emerald-50 p-1 rounded" title="Approve"><i class="material-icons text-sm">check</i></button>
            <button onclick="confirmAction('{{ route('admin.payment-clearance.sales.reject', $payment->payment_id) }}', 'Tolak Pembayaran?', '', 'danger')" class="text-rose-600 hover:bg-rose-50 p-1 rounded" title="Reject"><i class="material-icons text-sm">close</i></button>
        </div>

    @elseif($payment->status === 'completed')
        
        {{-- [REVISI TOMBOL HAPUS] --}}
        @if($payment->bulk_sales_payment_id)
            {{-- Jika Bulk Payment: Tampilkan Link ke Induk --}}
            <a href="{{ route('admin.bulk-sales-payments.show', $payment->bulk_sales_payment_id) }}" 
               class="text-indigo-500 hover:text-indigo-700 p-1 rounded inline-flex items-center gap-1" 
               title="Lihat Detail Bulk (Tidak bisa dihapus disini)">
                <i class="material-icons text-sm">open_in_new</i>
                <span class="text-[10px] font-bold">Bulk</span>
            </a>
        @else
            {{-- Jika Pembayaran Biasa: Tampilkan Tombol Hapus --}}
            <button onclick="confirmDelete('{{ route('admin.payments.destroy', $payment->payment_id) }}', 'Hapus Pembayaran?', 'Data akan dihapus dan jurnal dibalik.')" class="text-slate-400 hover:text-rose-500" title="Hapus">
                <i class="material-icons text-sm">delete</i>
            </button>
        @endif

    @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- TAB 3: RETUR --}}
            <div x-show="activeTab === 'returns'" style="display: none;">
                @if($invoice->returns->isEmpty()) <p class="text-center text-slate-400 italic py-8">Belum ada data retur.</p> 
                @else 
                    <div class="table-container">
                        <table class="table-modern">
                            <thead><tr><th>No. Retur</th><th>Tanggal</th><th>Tipe</th><th class="text-right">Total</th><th>Aksi</th></tr></thead>
                            <tbody>
                                @foreach($invoice->returns as $retur)
                                    <tr>
                                        <td><a href="{{ route('admin.sales-returns.show', $retur->return_id) }}" class="text-indigo-600 font-bold hover:underline">{{ $retur->return_number }}</a></td>
                                        <td>{{ $retur->return_date->format('d/m/Y') }}</td>
                                        <td>{{ $retur->return_handling_type == 'deduct_invoice' ? 'Potong Tagihan' : 'Simpan Kredit' }}</td>
                                        <td class="text-right font-bold text-rose-500">Rp {{ number_format($retur->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <button onclick="confirmDelete('{{ route('admin.sales-returns.destroy', $retur->return_id) }}', 'Batalkan Retur?')" class="text-slate-400 hover:text-rose-500"><i class="material-icons text-sm">delete</i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- TAB 4: ADJUSTMENTS (LOG KOREKSI) --}}
            <div x-show="activeTab === 'adjustments'" style="display: none;">
                 @if($invoice->adjustments->isEmpty()) <p class="text-center text-slate-400 italic py-8">Belum ada data koreksi.</p> 
                 @else 
                    <div class="table-container">
                        <table class="table-modern">
                            <thead><tr><th>Tanggal</th><th>Tipe</th><th>Detail Koreksi</th><th class="text-right">Nilai</th><th>Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($invoice->adjustments as $adj)
                                    <tr>
                                        <td class="align-top py-3">{{ $adj->adjustment_date->format('d/m/Y') }}</td>
                                        
                                        <td class="align-top py-3">
                                            @if($adj->type == 'debit_note')
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                                    <i class="material-icons text-[14px]">add_circle</i> Debit Note
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                    <i class="material-icons text-[14px]">remove_circle</i> Credit Note
                                                </span>
                                            @endif

                                            {{-- Indikator Tipe (Manual/Auto) --}}
                                            @if(!$adj->is_calculation_adjustment)
                                                <span class="block mt-1 text-[10px] text-slate-400 font-medium">(Revisi Otomatis)</span>
                                            @else
                                                <span class="block mt-1 text-[10px] text-indigo-500 font-medium">(Manual)</span>
                                            @endif
                                        </td>

                                        <td class="align-top py-3">
                                            <div class="text-sm text-slate-700 font-medium mb-1">{{ $adj->reason }}</div>
                                            
                                            {{-- Detail Perubahan Barang --}}
                                            @if(isset($adj->details['change_logs']) && count($adj->details['change_logs']) > 0)
                                                <div class="mt-2 bg-slate-50 rounded p-2 border border-slate-200">
                                                    <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Rincian Perubahan:</p>
                                                    <ul class="text-xs text-slate-600 list-disc list-inside space-y-0.5">
                                                        @foreach($adj->details['change_logs'] as $log)
                                                            <li>{{ $log }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </td>

                                        <td class="align-top text-right py-3 font-mono font-bold text-slate-700">
                                            Rp {{ number_format($adj->amount, 0, ',', '.') }}
                                        </td>

                                        <td class="align-top text-center py-3">
                                            <button type="button" onclick="confirmDelete('{{ route('admin.invoice-adjustments.destroy', $adj->adjustment_id) }}', 'Batalkan Koreksi?', 'Item invoice akan dikembalikan ke kondisi sebelum koreksi ini.')" class="text-slate-400 hover:text-rose-500 transition-colors">
                                                <i class="material-icons text-lg">delete</i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                 @endif
            </div>

        </div>
    </div>
    @endif

</div>

@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
<script>
    function confirmAction(url, title, text, color) {
        confirmDialog({ title: title || 'Konfirmasi', text: text || '', icon: 'question', confirmText: 'Ya, Lanjutkan', confirmColor: color || 'primary' }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form'); form.action = url; form.method = 'POST'; form.innerHTML = `@csrf`; document.body.appendChild(form); form.submit();
            }
        });
    }
    function confirmDelete(url, title, text) {
        confirmDialog({ title: title || 'Hapus Data?', text: text || 'Tindakan ini tidak dapat dibatalkan.', icon: 'warning', confirmText: 'Ya, Hapus', confirmColor: 'danger' }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form'); form.action = url; form.method = 'POST'; form.innerHTML = `@csrf @method('DELETE')`; document.body.appendChild(form); form.submit();
            }
        });
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('paymentForm', () => ({
            remainingDue: {{ $sisaTagihan > 0 ? $sisaTagihan : 0 }},
            creditBalance: {{ $invoice->client->balance }},
            useCredit: false,
            creditAmount: 0,
            cashAmount: {{ $sisaTagihan > 0 ? $sisaTagihan : 0 }},
            isProcessing: false,
            requiresProof: false,
            requiresRef: false,
            anInstance: null,

            init() {
                const el = this.$refs.amountInput;
                if (el) {
                    this.anInstance = new AutoNumeric(el, { ...window.defaultAutoNumericOptions, minimumValue: '0' });
                    this.anInstance.set(this.cashAmount);
                    el.addEventListener('autoNumeric:rawValueModified', (e) => { this.cashAmount = parseFloat(e.detail.newRawValue) || 0; });
                }
            },

            updateCalculation() {
                if (this.useCredit) {
                    this.creditAmount = Math.min(this.creditBalance, this.remainingDue);
                    let sisa = Math.max(0, this.remainingDue - this.creditAmount);
                    this.cashAmount = sisa;
                    if(this.anInstance) this.anInstance.set(sisa);
                } else {
                    this.creditAmount = 0;
                    this.cashAmount = this.remainingDue;
                    if(this.anInstance) this.anInstance.set(this.remainingDue);
                }
            },

            setMaxAmount() {
                let targetCash = Math.max(0, this.remainingDue - this.creditAmount);
                this.cashAmount = targetCash;
                if(this.anInstance) this.anInstance.set(targetCash);
            },

            get balanceAfterPay() { return Math.max(0, this.remainingDue - this.creditAmount); },

            initMethodSelect(el) {
                new TomSelect(el.querySelector('select'), { ...window.defaultTomSelectConfig, dropdownParent: 'body', onChange: (val) => this.updateConfig(val, el.querySelector('select')) });
            },
            initBankSelect(el) { new TomSelect(el.querySelector('select'), { ...window.defaultTomSelectConfig, dropdownParent: 'body' }); },

            updateConfig(val, el) {
                if(!val) { this.requiresProof = false; this.requiresRef = false; return; }
                const opt = el.querySelector(`option[value="${val}"]`);
                const cfg = opt ? opt.dataset.config : 'none';
                this.requiresProof = (cfg === 'proof_only' || cfg === 'proof_and_reference');
                this.requiresRef = (cfg === 'reference_only' || cfg === 'proof_and_reference');
            },

            async payWithMidtrans() {
                this.isProcessing = true;
                try {
                    const response = await fetch("{{ route('admin.invoices.get-snap-token', $invoice->invoice_id) }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ use_credit: this.useCredit })
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        window.snap.pay(data.snap_token, {
                            onSuccess: function(result) { window.location.reload(); },
                            onPending: function(result) { window.location.reload(); },
                            onError: function(result) { alert("Pembayaran gagal!"); },
                            onClose: function() {}
                        });
                    } else if (data.status === 'full_credit') {
                        alert("Saldo deposit mencukupi. Silakan gunakan tombol Simpan Manual di bawah.");
                    } else {
                        alert("Error: " + data.message);
                    }
                } catch (error) {
                    console.error(error); alert("Gagal menghubungkan ke Payment Gateway.");
                } finally { this.isProcessing = false; }
            },
            formatRupiah(num) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num); }
        }));
    });
</script>
@endpush
@endsection
@extends('admin.layouts.app')

@section('title', 'Detail PO #' . $purchaseOrder->po_number)

@section('content')
<div class="max-w-6xl mx-auto pb-10">

    {{-- =====================================================================
         1. HEADER & ACTION BUTTONS (POSISI KEMBALI SEPERTI SEMULA)
         ===================================================================== --}}
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-6">
        {{-- KIRI: Judul & Info --}}
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h2 class="page-title text-3xl">{{ $purchaseOrder->po_number }}</h2>
                @php
                    $badgeClass = match($purchaseOrder->status) {
                        'draft' => 'badge-secondary',
                        'ordered' => 'badge-info',
                        'completed' => 'badge-success',
                        'cancelled' => 'badge-danger',
                        default => 'badge-secondary'
                    };
                    $iconClass = match($purchaseOrder->status) {
                        'draft' => 'edit_note',
                        'ordered' => 'local_shipping',
                        'completed' => 'check_circle',
                        'cancelled' => 'block',
                        default => 'help'
                    };
                    $statusLabel = match($purchaseOrder->status) {
                        'draft' => 'Draft',
                        'ordered' => 'Dipesan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($purchaseOrder->status)
                    };
                @endphp
                <span class="badge {{ $badgeClass }} flex items-center gap-1">
                    <i class="material-icons text-[14px]">{{ $iconClass }}</i> {{ $statusLabel }}
                </span>
            </div>

            <div class="flex flex-col gap-1 text-sm text-slate-500">
                <div class="flex items-center gap-2">
                    <i class="material-icons text-base text-slate-400">store</i>
                    <span>Supplier: <a href="{{ route('admin.suppliers.show', $purchaseOrder->supplier_id) }}" class="font-bold text-indigo-600 hover:underline">{{ $purchaseOrder->supplier->supplier_name }}</a></span>
                    <span class="text-slate-300">|</span>
                    <span>Tgl: {{ $purchaseOrder->order_date->format('d M Y') }}</span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <i class="material-icons text-base text-slate-400">receipt</i>
                    <span>No. Faktur: <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $purchaseOrder->supplier_invoice_number ?? '(Belum diisi)' }}</span></span>
                    <button type="button" onclick="document.getElementById('edit-inv-modal').classList.remove('hidden')" class="text-indigo-500 hover:text-indigo-700 ml-1"><i class="material-icons text-base">edit</i></button>
                </div>
            </div>
        </div>

        {{-- KANAN: Tombol Aksi (Termasuk Back Button) --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Tombol Back --}}
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary" title="Kembali ke Daftar">
                <i class="material-icons text-lg">arrow_back</i>
            </a>
            
            @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                <a href="{{ route('admin.purchase-orders.edit', $purchaseOrder->po_id) }}" class="btn btn-primary flex items-center gap-2">
                    <i class="material-icons text-lg">edit</i> Edit PO
                </a>
            @endif

            @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                <button type="button" onclick="confirmReceive()" class="btn btn-success shadow-sm flex items-center gap-2">
                    <i class="material-icons text-lg">inventory</i> Terima Barang
                </button>
                <form id="receive-form" action="{{ route('admin.purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST" class="hidden">@csrf</form>
            @endif

            {{-- DROPDOWN OPSI LAINNYA --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="btn btn-secondary flex items-center gap-1">
                    <span>Opsi Lainnya</span><i class="material-icons text-lg">arrow_drop_down</i>
                </button>
                
                <div x-show="open" style="display: none;" 
                     class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 z-50 overflow-hidden divide-y divide-slate-100 dark:divide-slate-700" 
                     x-transition.origin.top.right>
                    
                    <a href="{{ route('admin.purchase-orders.pdf', $purchaseOrder->po_id) }}" target="_blank" class="block px-4 py-3 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                        <i class="material-icons text-slate-400">print</i> Cetak PDF
                    </a>

                    @if($purchaseOrder->status != 'cancelled')
                        
                        @if($purchaseOrder->status != 'draft')
                            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-900/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Koreksi / Revisi</div>
                            
                            <a href="{{ route('admin.purchase-order-adjustments.create.manual', $purchaseOrder->po_id) }}" class="block px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                <i class="material-icons text-indigo-500">post_add</i> Penyesuaian Manual
                            </a>
                            <a href="{{ route('admin.purchase-order-adjustments.create.auto', $purchaseOrder->po_id) }}" class="block px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                                <i class="material-icons text-indigo-500">auto_fix_high</i> Koreksi Otomatis
                            </a>
                        @endif

                        @if($purchaseOrder->status != 'completed')
                            <button type="button" onclick="confirmCancel()" class="w-full text-left px-4 py-3 text-sm text-rose-600 hover:bg-rose-50 hover:text-rose-700 flex items-center gap-3 transition-colors border-t border-slate-100">
                                <i class="material-icons text-rose-500">cancel</i> Batalkan Pesanan
                            </button>
                            <form id="cancel-form" action="{{ route('admin.purchase-orders.cancel', $purchaseOrder->po_id) }}" method="POST" class="hidden">@csrf</form>
                        @endif

                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- =====================================================================
         2. STATS GRID & SALDO
         ===================================================================== --}}
    @php
        $totalDebitNotes = $purchaseOrder->adjustments->where('type', 'debit_note')->sum('amount');
        $totalCreditNotes = $purchaseOrder->adjustments->where('type', 'credit_note')->sum('amount');
        $netAdjustment = $totalDebitNotes - $totalCreditNotes;
        $originalTotal = $purchaseOrder->grand_total - $netAdjustment;
        $hasAdjustment = abs($netAdjustment) > 0.01;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- CARD 1: TOTAL TAGIHAN --}}
        <div class="card p-5 bg-gradient-to-br from-slate-800 to-slate-900 text-white border-none shadow-lg relative overflow-hidden group">
            <div class="relative z-10">
                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Tagihan (Nett)</p>
                <div class="flex flex-col">
                    <h3 class="text-2xl font-bold">Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}</h3>
                    @if($hasAdjustment)
                         <div class="text-xs text-slate-400 mt-1 flex items-center gap-2 bg-white/10 w-fit px-2 py-1 rounded">
                            <span class="line-through decoration-slate-400 opacity-70" title="Total Awal">Rp {{ number_format($originalTotal, 0, ',', '.') }}</span>
                            @if($netAdjustment < 0)
                                <span class="text-emerald-400 font-bold flex items-center gap-0.5" title="Total Hemat">
                                    <i class="material-icons text-[10px]">arrow_downward</i> {{ number_format(abs($netAdjustment), 0, ',', '.') }}
                                </span>
                            @else
                                <span class="text-rose-400 font-bold flex items-center gap-0.5" title="Total Tambahan">
                                    <i class="material-icons text-[10px]">arrow_upward</i> {{ number_format($netAdjustment, 0, ',', '.') }}
                                </span>
                            @endif
                         </div>
                    @endif
                </div>
            </div>
            <i class="material-icons absolute right-[-10px] bottom-[-10px] text-[80px] text-white/5 rotate-12 group-hover:scale-110 transition-transform">payments</i>
        </div>

        {{-- CARD 2: SUDAH DIBAYAR --}}
        <div class="card p-5">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Sudah Dibayar</p>
                    <h3 class="text-2xl font-bold text-emerald-600">Rp {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</h3>
                </div>
                <div class="text-right">
                    @php
                        $paymentStatusClass = match($purchaseOrder->payment_status) {
                            'paid' => 'badge-success',
                            'partially_paid' => 'badge-warning',
                            default => 'badge-danger'
                        };
                        $paymentStatusLabel = match($purchaseOrder->payment_status) {
                            'paid' => 'LUNAS',
                            'partially_paid' => 'SEBAGIAN',
                            default => 'BELUM BAYAR'
                        };
                    @endphp
                    <span class="badge {{ $paymentStatusClass }}">{{ $paymentStatusLabel }}</span>
                </div>
            </div>
        </div>

        {{-- CARD 3: SISA HUTANG --}}
        <div class="card p-5 border-l-4 {{ $purchaseOrder->remaining_balance > 0.01 ? 'border-rose-500' : 'border-emerald-500' }}">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Sisa Hutang</p>
                    <h3 class="text-2xl font-bold {{ $purchaseOrder->remaining_balance > 0.01 ? 'text-rose-600' : 'text-slate-700' }}">
                        Rp {{ number_format(max(0, $purchaseOrder->remaining_balance), 0, ',', '.') }}
                    </h3>
                </div>
                @if($purchaseOrder->remaining_balance > 0.01)
                    <i class="material-icons text-rose-200 text-4xl">pending</i>
                @else
                    <i class="material-icons text-emerald-200 text-4xl">verified</i>
                @endif
            </div>
        </div>
    </div>

    {{-- =====================================================================
         3. TABS CONTENT
         ===================================================================== --}}
    <div x-data="{ activeTab: 'items' }" class="mb-10">
        
        {{-- TAB NAV --}}
        <div class="flex gap-2 mb-4 border-b border-slate-200 dark:border-slate-700">
            <button @click="activeTab = 'items'" 
                    :class="activeTab === 'items' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 border-b-2 font-bold text-sm transition-colors flex items-center gap-2">
                <i class="material-icons text-sm">list_alt</i> Rincian Barang
            </button>
            <button @click="activeTab = 'payments'" 
                    :class="activeTab === 'payments' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 border-b-2 font-bold text-sm transition-colors flex items-center gap-2">
                <i class="material-icons text-sm">payments</i> Pembayaran
            </button>
            <button @click="activeTab = 'adjustments'" 
                    :class="activeTab === 'adjustments' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 border-b-2 font-bold text-sm transition-colors flex items-center gap-2">
                <i class="material-icons text-sm">tune</i> Retur & Penyesuaian
                @if($purchaseOrder->adjustments->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full bg-slate-100 text-[10px] text-slate-600 border border-slate-200">{{ $purchaseOrder->adjustments->count() }}</span>
                @endif
            </button>
            <button @click="activeTab = 'info'" 
                    :class="activeTab === 'info' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="px-4 py-2 border-b-2 font-bold text-sm transition-colors flex items-center gap-2">
                <i class="material-icons text-sm">info</i> Info Detail
            </button>
        </div>

        {{-- TAB 1: ITEMS --}}
        <div x-show="activeTab === 'items'" x-transition.opacity>
            <div class="card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <div class="table-container border-0 rounded-none shadow-none">
                    <table class="table-modern w-full">
                        <thead>
                            <tr>
                                <th class="w-[40%]">Produk</th>
                                <th class="w-[15%] text-right">Harga Satuan</th>
                                <th class="w-[10%] text-center">Qty</th>
                                <th class="w-[15%] text-center">Diskon</th>
                                <th class="w-[20%] text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td>
                                    <div class="font-medium text-slate-700 dark:text-slate-200">{{ $item->product->product_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->product->product_code }}</div>
                                </td>
                                <td class="text-right">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit->name ?? 'Unit' }}</td>
                                <td class="text-center">
                                    @if($item->discounts->count() > 0)
                                        @foreach($item->discounts as $d) <span class="badge badge-secondary text-[10px]">{{ $d->percentage + 0 }}%</span> @endforeach
                                    @else <span class="text-slate-400">-</span> @endif
                                </td>
                                <td class="text-right font-medium text-slate-700 dark:text-white">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-slate-800/30 text-sm">
                             <tr><td colspan="4" class="text-right py-2 text-slate-500">Total Awal (Original)</td><td class="text-right py-2 px-6 font-medium">Rp {{ number_format($originalTotal, 0, ',', '.') }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @if($hasAdjustment)
                <div class="mt-4 bg-indigo-50 border border-indigo-100 p-3 rounded-lg text-center">
                    <p class="text-sm text-indigo-700 flex items-center justify-center gap-1">
                        <i class="material-icons text-sm">info</i>
                        Nota ini telah direvisi. Total tagihan telah berubah. Lihat detail di tab <strong>Retur & Penyesuaian</strong>.
                    </p>
                </div>
            @endif
        </div>

        {{-- TAB 2: PAYMENTS --}}
        <div x-show="activeTab === 'payments'" x-transition.opacity style="display: none;">
            
            {{-- FORM INPUT PEMBAYARAN BARU --}}
            @if($purchaseOrder->remaining_balance > 0.01 && $purchaseOrder->status != 'cancelled')
                <div class="card mb-6 border border-indigo-100 bg-indigo-50/30 dark:bg-slate-800 dark:border-slate-700"
                     x-data="paymentForm({{ $purchaseOrder->supplier->balance }})"
                     id="payment-section">
                    <div class="card-header py-3 bg-transparent">
                        <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-400">Catat Pembayaran Keluar (Internal)</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.purchase-orders.payments.store', $purchaseOrder->po_id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                                <div>
                                    <label class="form-label text-[10px]">Tanggal Bayar</label>
                                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="form-input text-sm" required>
                                </div>
                                <div>
                                    <label class="form-label text-[10px]">Total Bayar (Rp)</label>
                                    <input type="text" x-ref="amountInput" class="form-input text-sm text-right font-bold text-indigo-700 autonumeric" required>
                                    <input type="hidden" name="amount" :value="rawAmount">
                                </div>

                                {{-- CHECKBOX GUNAKAN DEPOSIT --}}
                                <div class="md:col-span-2 flex flex-col justify-center pt-5">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="use_debit_balance" id="use_debit_balance" class="form-checkbox rounded text-indigo-600 w-5 h-5" value="1" 
                                            x-model="useDeposit" @change="recalculatePaymentAmount()"
                                            @if($purchaseOrder->supplier->balance <= 0) disabled @endif>
                                        <label for="use_debit_balance" class="text-sm text-slate-700 font-medium cursor-pointer select-none">
                                            Potong Deposit Supplier
                                            <span class="text-slate-400 text-xs block font-normal">
                                                Saldo tersedia: <strong>Rp {{ number_format($purchaseOrder->supplier->balance, 0, ',', '.') }}</strong>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                {{-- RINCIAN ALOKASI --}}
                                <div class="md:col-span-4 bg-white p-3 rounded-lg border border-slate-200" x-show="useDeposit" style="display:none;">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="block text-xs text-slate-400">Potong Deposit</span>
                                            <span class="font-bold text-indigo-600" x-text="formatRupiah(Math.min(supplierBalance, rawAmount))"></span>
                                        </div>
                                        <div>
                                            <span class="block text-xs text-slate-400">Sisa Bayar (Transfer/Kas)</span>
                                            <span class="font-bold text-emerald-600" x-text="formatRupiah(Math.max(0, rawAmount - Math.min(supplierBalance, rawAmount)))"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- FIELDS METODE BAYAR (HANYA JIKA ADA SISA CASH) --}}
                                <div class="md:col-span-4 grid grid-cols-1 md:grid-cols-4 gap-4" x-show="needsCashPayment" x-transition>
                                    <div class="md:col-span-1">
                                        <label class="form-label text-[10px]">Metode Pembayaran</label>
                                        <select name="payment_method_id" x-ref="methodSelect" class="tom-select">
                                            <option value="">Pilih Metode...</option>
                                            @foreach($paymentMethods as $pm)
                                                {{-- ✅ PENTING: Gunakan 'internal_input_config' --}}
                                                <option value="{{ $pm->payment_method_id }}" data-config="{{ $pm->internal_input_config }}">{{ $pm->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="form-label text-[10px]">Dari Akun Bank</label>
                                        <select name="company_bank_account_id" x-ref="bankSelect" class="tom-select">
                                            <option value="">Pilih Sumber Dana...</option>
                                            @foreach($companyBankAccounts as $ba)
                                                <option value="{{ $ba->company_bank_account_id }}">{{ $ba->bank_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-1" x-show="needsRef" style="display:none;">
                                        <label class="form-label text-[10px]">No. Referensi</label>
                                        <input type="text" name="reference_number" class="form-input text-sm" placeholder="Contoh: TRF-123">
                                    </div>
                                    
                                    {{-- ✅ FLOWBITE UPLOAD --}}
                                    <div class="md:col-span-1" x-show="needsProof" style="display:none;">
                                        <label class="form-label text-[10px]">Bukti Transfer</label>
                                        <div class="flex items-center justify-center w-full">
                                            <label for="inputProof" class="flex flex-col items-center justify-center w-full h-10 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600 transition-colors">
                                                <div class="flex flex-row items-center justify-center gap-2 pt-1">
                                                    <i class="material-icons text-gray-400 text-sm">cloud_upload</i>
                                                    <p class="text-[10px] text-gray-500 dark:text-gray-400" id="fileNameDisplay">Klik upload</p>
                                                </div>
                                                <input id="inputProof" name="proof_of_payment" type="file" class="hidden" accept="image/*" />
                                            </label>
                                        </div> 
                                    </div>
                                </div>

                                <div class="md:col-span-4">
                                    <label class="form-label text-[10px]">Catatan</label>
                                    <input type="text" name="notes" class="form-input text-sm" placeholder="Catatan pembayaran...">
                                </div>
                                
                                <div class="md:col-span-4 mt-2" x-show="excessAmount > 0">
                                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-2 text-sm text-blue-700">
                                        <i class="material-icons text-blue-500 text-lg">info</i>
                                        <div>
                                            <p class="font-bold">Kelebihan Bayar Terdeteksi</p>
                                            <p>Total bayar melebihi sisa hutang. Kelebihan <strong>Rp <span x-text="formatRupiah(excessAmount)"></span></strong> akan disimpan sebagai <strong>Deposit Supplier</strong>.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-4 mt-2">
                                    <button type="submit" class="btn btn-primary w-full justify-center">
                                        <i class="material-icons text-sm mr-1">save</i> Simpan Pembayaran
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- TABEL RIWAYAT PEMBAYARAN --}}
            <div class="table-container">
                <table class="table-modern w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Metode / Keterangan</th>
                            <th>Akun Bank / Referensi</th>
                            <th>Penerima (User)</th>
                            <th class="text-right">Total Keluar</th>
                            <th class="text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrder->payments as $payment)
                            @php
                                // Ambil data deposit jika ada ledger terkait
                                $depositLedger = \App\Models\SupplierLedger::where('reference_type', \App\Models\PurchaseOrderPayment::class)
                                                                            ->where('reference_id', $payment->id)
                                                                            ->where('type', 'debit') 
                                                                            ->first();
                                $depositAmount = $depositLedger ? abs($depositLedger->amount) : 0;
                            @endphp
                            <tr>
                                <td class="text-sm align-top">
                                    {{ $payment->payment_date->format('d/m/Y') }}
                                    <div class="text-[10px] text-slate-400 mt-1">ID: #{{ $payment->id }}</div>
                                </td>
                                <td class="align-top">
                                    <div class="font-medium text-slate-700 dark:text-slate-200 text-xs">
                                        {{ $payment->paymentMethod->name ?? 'Deposit / Lainnya' }}
                                    </div>
                                    @if($depositAmount > 0)
                                        <div class="text-xs text-emerald-600 font-bold mt-1 p-1 bg-emerald-50 rounded inline-block border border-emerald-100">
                                            <i class="material-icons text-[10px] align-middle">savings</i>
                                            Potong Deposit: Rp {{ number_format($depositAmount, 0, ',', '.') }}
                                        </div>
                                    @endif
                                    @if($payment->notes) <div class="text-[10px] text-slate-500 italic mt-1 border-t border-slate-100 pt-1">"{{ $payment->notes }}"</div> @endif
                                </td>
                                <td class="align-top">
                                    <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                        <i class="material-icons text-[10px]">account_balance</i>
                                        {{ $payment->companyBankAccount->bank_name ?? '-' }}
                                    </div>
                                    @if($payment->reference_number)
                                        <div class="text-xs font-mono text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded inline-block mt-1">
                                            Ref: {{ $payment->reference_number }}
                                        </div>
                                    @endif
                                    @if($payment->proof_of_payment_path)
                                        <div class="mt-1">
                                            <button onclick="showProof('{{ asset('storage/' . $payment->proof_of_payment_path) }}')" class="text-indigo-600 hover:text-indigo-800 text-xs flex items-center gap-1 underline decoration-dashed">
                                                <i class="material-icons text-[12px]">image</i> Lihat Bukti
                                            </button>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-sm align-top">{{ $payment->receivedBy->full_name ?? '-' }}</td>
                                <td class="text-right font-bold text-slate-700 dark:text-white align-top">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center align-top">
                                    <button type="button" onclick="confirmDeletePayment('{{ $payment->id }}')" class="btn-icon btn-sm btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50" title="Hapus / Reversal">
                                        <i class="material-icons text-sm">delete</i>
                                    </button>
                                    <form id="delete-payment-{{ $payment->id }}" action="{{ route('admin.purchase-orders.payments.destroy', $payment->id) }}" method="POST" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-8 text-slate-400 text-sm">Belum ada riwayat pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 3: ADJUSTMENTS --}}
        <div x-show="activeTab === 'adjustments'" x-transition.opacity style="display: none;">
            <div class="card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-0">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center">
                    <h4 class="font-bold text-slate-700 dark:text-white">Riwayat Penyesuaian (Audit Trail)</h4>
                    @if($purchaseOrder->status != 'draft' && $purchaseOrder->status != 'cancelled')
                        <div class="flex gap-2">
                            <a href="{{ route('admin.purchase-order-adjustments.create.manual', $purchaseOrder->po_id) }}" class="btn btn-sm btn-white border border-slate-300 shadow-sm text-slate-700 hover:bg-slate-50"><i class="material-icons text-sm">post_add</i> Manual</a>
                            <a href="{{ route('admin.purchase-order-adjustments.create.auto', $purchaseOrder->po_id) }}" class="btn btn-sm btn-white border border-slate-300 shadow-sm text-slate-700 hover:bg-slate-50"><i class="material-icons text-sm">auto_fix_high</i> Otomatis</a>
                        </div>
                    @endif
                </div>
                <div class="table-container border-0 rounded-none shadow-none">
                     <table class="table-modern w-full">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Nota</th>
                                <th>Alasan</th>
                                <th class="text-right">Nominal</th>
                                <th class="text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseOrder->adjustments as $adj)
                                <tr>
                                    <td class="text-sm">{{ $adj->adjustment_date->format('d M Y') }}</td>
                                    <td>
                                        @if($adj->type == 'credit_note')
                                            <span class="badge badge-success text-[10px]">Credit Note (Potongan)</span>
                                        @else
                                            <span class="badge badge-danger text-[10px]">Debit Note (Tambahan)</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-slate-600 italic max-w-md truncate">{{ $adj->reason }}</td>
                                    <td class="text-right font-mono font-bold text-slate-700">Rp {{ number_format($adj->amount, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <button onclick="confirmDeleteAdjustment('{{ $adj->adjustment_id }}')" class="btn-icon btn-sm btn-danger" title="Hapus">
                                            <i class="material-icons text-sm">delete</i>
                                        </button>
                                        <form id="delete-adj-{{ $adj->adjustment_id }}" action="{{ route('admin.purchase-order-adjustments.destroy', $adj->adjustment_id) }}" method="POST" class="hidden">
                                            @csrf @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center p-4 text-slate-400 text-sm">Tidak ada penyesuaian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabel Retur Fisik --}}
            <div class="mt-6">
                 <h4 class="font-bold text-slate-700 dark:text-white mb-3 px-1">Riwayat Retur Fisik (Barang)</h4>
                 <div class="table-container">
                    <table class="table-modern w-full">
                        <thead><tr><th>No. Retur</th><th>Tanggal</th><th>Jenis</th><th class="text-right">Nilai Retur</th><th class="text-center">Status</th></tr></thead>
                        <tbody>
                            @forelse($purchaseOrder->returns as $ret)
                                <tr>
                                    <td class="font-medium text-indigo-600"><a href="{{ route('admin.purchase-returns.show', $ret->return_id) }}" class="hover:underline">{{ $ret->return_number }}</a></td>
                                    <td class="text-sm">{{ $ret->return_date->format('d/m/Y') }}</td>
                                    <td>{!! $ret->return_handling_type == 'deduct_invoice' ? '<span class="badge badge-warning text-[10px]">Potong Hutang</span>' : '<span class="badge badge-info text-[10px]">Simpan Deposit</span>' !!}</td>
                                    <td class="text-right font-medium">Rp {{ number_format($ret->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-center text-xs text-slate-500">Selesai</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-slate-400 text-sm">Tidak ada data retur fisik.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 4: INFO --}}
        <div x-show="activeTab === 'info'" class="p-6 animate-enter" style="display: none;">
             @include('admin.purchase_orders.partials.tab_info')
        </div>
    </div>
    
    {{-- MODAL EDIT NO FAKTUR --}}
    <div id="edit-inv-modal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-4">Update No. Faktur Supplier</h3>
            <form action="{{ route('admin.purchase-orders.addSupplierInvoice', $purchaseOrder->po_id) }}" method="POST">
                @csrf
                <input type="text" name="supplier_invoice_number" value="{{ $purchaseOrder->supplier_invoice_number }}" class="form-input mb-4" placeholder="Nomor Invoice dari Supplier">
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('edit-inv-modal').classList.add('hidden')" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL BUKTI PEMBAYARAN (IMAGE NATIVE) --}}
    <div id="imageModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-[60] hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="relative w-full max-w-4xl max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Bukti Pembayaran</h3>
                    <button onclick="closeProof()" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <i class="material-icons text-base">close</i>
                    </button>
                </div>
                <div class="p-4 space-y-4 flex justify-center bg-gray-50 rounded-b-lg">
                     <img id="img-preview-full" src="" alt="Bukti" class="max-w-full max-h-[80vh] object-contain rounded-md shadow-sm">
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    const methodConfigs = @json($paymentMethods->pluck('internal_input_config', 'payment_method_id'));

    document.addEventListener('alpine:init', () => {
        Alpine.data('paymentForm', (supplierBalance = 0) => ({
            methodId: '',
            remainingBalance: {{ $purchaseOrder->remaining_balance }},
            supplierBalance: parseFloat(supplierBalance),
            
            rawAmount: 0,
            useDeposit: false,
            excessAmount: 0,
            
            needsProof: false,
            needsRef: false,
            needsCashPayment: false, 
            anElement: null,
            
            init() {
                // Init Tom Select
                if(this.$refs.methodSelect) {
                    new TomSelect(this.$refs.methodSelect, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => {
                            this.methodId = value;
                            this.updateConfig();
                        }
                    });
                }
                if(this.$refs.bankSelect) {
                    new TomSelect(this.$refs.bankSelect, window.defaultTomSelectConfig);
                }

                // Init AutoNumeric
                this.anElement = new AutoNumeric(this.$refs.amountInput, {
                    ...window.defaultAutoNumericOptions,
                    minimumValue: '0',
                });
                
                // Set default val
                this.rawAmount = this.remainingBalance;
                this.anElement.set(this.rawAmount);

                // Listen change
                this.$refs.amountInput.addEventListener('autoNumeric:rawValueModified', e => {
                    this.rawAmount = parseFloat(e.detail.newRawValue) || 0;
                    this.calculateCalculation();
                });
                
                // Initial Calc
                this.calculateCalculation();
                
                // File Input Label Logic (Flowbite style)
                const fileInput = document.getElementById('inputProof');
                const fileNameDisplay = document.getElementById('fileNameDisplay');
                if(fileInput && fileNameDisplay) {
                    fileInput.addEventListener('change', function(e) {
                         if(e.target.files.length > 0) {
                             fileNameDisplay.textContent = e.target.files[0].name;
                             fileNameDisplay.classList.add('text-indigo-600', 'font-bold');
                         } else {
                             fileNameDisplay.textContent = "Klik upload";
                             fileNameDisplay.classList.remove('text-indigo-600', 'font-bold');
                         }
                    });
                }
            },

            updateConfig() {
                if (!this.methodId) {
                    this.needsProof = false;
                    this.needsRef = false;
                    return;
                }
                const config = methodConfigs[this.methodId] || 'none';
                this.needsProof = config === 'proof_only' || config === 'proof_and_reference';
                this.needsRef = config === 'reference_only' || config === 'proof_and_reference';
            },

            // Dijalankan saat Checkbox Deposit berubah
            recalculatePaymentAmount() {
                // Reset amount ke default remaining dulu
                let amountToPay = this.remainingBalance;
                
                // Trigger kalkulasi ulang
                this.rawAmount = amountToPay;
                this.anElement.set(amountToPay);
                this.calculateCalculation();
            },

            calculateCalculation() {
                let effectiveTotal = this.rawAmount;
                let cashNeeded = this.rawAmount;

                // Jika pakai deposit
                if(this.useDeposit && this.supplierBalance > 0) {
                     const depositUsable = Math.min(this.supplierBalance, this.remainingBalance);
                     cashNeeded = Math.max(0, this.rawAmount - depositUsable);
                }
                
                // Toggle tampilan metode bayar
                this.needsCashPayment = cashNeeded > 0.01;

                // Cek Kelebihan Bayar
                if (effectiveTotal > this.remainingBalance) {
                     this.excessAmount = effectiveTotal - this.remainingBalance;
                } else {
                     this.excessAmount = 0;
                }
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'decimal',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(value);
            }
        }));
    });

    function confirmReceive() {
        window.confirmDialog({
            title: 'Konfirmasi Terima Barang?',
            text: 'Stok barang akan bertambah dan jurnal hutang akan dicatat otomatis. Pastikan barang fisik sudah diterima.',
            icon: 'info',
            confirmButtonText: 'Ya, Terima Barang',
            confirmButtonColor: '#10b981', // Hijau
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('receive-form').submit();
        });
    }

    function confirmCancel() {
        window.confirmDialog({
            title: 'Batalkan PO?',
            text: 'Stok akan dikembalikan (jika sudah diterima) dan jurnal akan dibalik. Aksi ini tidak dapat dibatalkan.',
            icon: 'warning',
            confirmButtonText: 'Ya, Batalkan',
            confirmButtonColor: '#ef4444', // Merah
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('cancel-form').submit();
        });
    }

    function confirmDeletePayment(id) {
        window.confirmDialog({
            title: 'Hapus Pembayaran?',
            text: 'Pembayaran akan dihapus dan jurnal keuangan akan dibalik. Hutang akan bertambah kembali.',
            icon: 'warning',
            confirmButtonText: 'Ya, Hapus',
            confirmButtonColor: '#ef4444',
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('delete-payment-' + id).submit();
        });
    }

    function confirmDeleteAdjustment(id) {
        window.confirmDialog({
            title: 'Hapus Penyesuaian?',
            text: 'Nota Debit/Kredit ini akan dihapus. Saldo hutang akan dikalkulasi ulang.',
            icon: 'warning',
            confirmButtonText: 'Ya, Hapus',
            confirmButtonColor: '#ef4444',
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('delete-adj-' + id).submit();
        });
    }

    // LOGIKA MODAL IMAGE (KHUSUS)
    function showProof(url) { 
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('img-preview-full');
        img.src = url;
        modal.classList.remove('hidden');
    }

    function closeProof() {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('img-preview-full');
        modal.classList.add('hidden');
        setTimeout(() => { img.src = ''; }, 300);
    }

    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeProof();
        }
    });
</script>
@endpush  
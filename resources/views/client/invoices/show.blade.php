@extends('client.layouts.app')

@section('title', 'Tagihan #' . $invoice->invoice_number)

@section('content')

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('client.invoices.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition-colors w-fit mb-1">
                <i class="material-icons text-sm">arrow_back</i>
                <span class="text-sm font-medium">Kembali ke Daftar</span>
            </a>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Invoice #{{ $invoice->invoice_number }}</h1>
            <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                <i class="material-icons text-sm">event</i> {{ $invoice->order_date->format('d F Y') }}
                <span class="mx-1">•</span>
                <span class="{{ $invoice->due_date < now() && $invoice->status != 'paid' ? 'text-red-500 font-bold' : '' }}">
                    Jatuh Tempo: {{ $invoice->due_date->format('d F Y') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            {{-- Status Badge --}}
            @php
                $statusColor = match($invoice->status) {
                    'paid' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'partially_paid' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'unpaid' => 'bg-rose-100 text-rose-700 border-rose-200',
                    'cancelled' => 'bg-slate-100 text-slate-700 border-slate-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $statusLabel = match($invoice->status) {
                    'paid' => 'Lunas',
                    'partially_paid' => 'Belum Lunas (Sebagian)',
                    'unpaid' => 'Belum Dibayar',
                    'cancelled' => 'Dibatalkan',
                    default => ucfirst($invoice->status)
                };
            @endphp
            <div class="px-4 py-2 rounded-xl border flex items-center gap-2 {{ $statusColor }}">
                <span class="font-bold text-sm uppercase tracking-wide">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- LEFT COLUMN: Invoice Items --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Rincian Produk --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Rincian Produk</h3>
                </div>
                <div class="table-container border-0 shadow-none rounded-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-right">Harga</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td>
                                        <div class="font-medium text-slate-700 dark:text-white">
                                            {{ $item->product->product_name }}
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $item->product->product_code }}</div>
                                    </td>
                                    <td class="text-right">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                    <td class="text-right font-medium text-slate-800 dark:text-white">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Summary Section --}}
                <div class="p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex flex-col gap-2 items-end text-sm">
                        <div class="w-full sm:w-1/2 flex justify-between text-slate-500">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                        </div>
                        
                        @if($invoice->discount_amount > 0)
                            <div class="w-full sm:w-1/2 flex justify-between text-emerald-600">
                                <span>Diskon ({{ $invoice->discount_percentage }}%)</span>
                                <span>- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        @foreach($invoice->taxes as $tax)
                            <div class="w-full sm:w-1/2 flex justify-between text-slate-500">
                                <span>{{ $tax->name }} ({{ $tax->rate }}%)</span>
                                <span>+ Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</span>
                            </div>
                        @endforeach

                        @if($invoice->additionalCosts->count() > 0)
                             @foreach($invoice->additionalCosts as $cost)
                                <div class="w-full sm:w-1/2 flex justify-between text-slate-500">
                                    <span>{{ $cost->description }}</span>
                                    <span>+ Rp {{ number_format($cost->amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        @endif

                        <div class="w-full border-t border-slate-200 dark:border-slate-700 my-2"></div>
                        
                        <div class="w-full sm:w-1/2 flex justify-between font-extrabold text-lg text-slate-800 dark:text-white">
                            <span>Total Tagihan</span>
                            <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="w-full sm:w-1/2 flex justify-between font-medium text-emerald-600 mt-1">
                            <span>Sudah Dibayar</span>
                            <span>- Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                        </div>

                        @if($invoice->total_deducting_returns > 0)
                            <div class="w-full sm:w-1/2 flex justify-between font-medium text-amber-600">
                                <span>Retur (Potong Tagihan)</span>
                                <span>- Rp {{ number_format($invoice->total_deducting_returns, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="w-full sm:w-1/2 flex justify-between font-bold text-rose-600 text-base mt-2 pt-2 border-t border-slate-200 dark:border-slate-700 border-dashed">
                            <span>Sisa Tagihan</span>
                            <span>Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment History --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Riwayat Pembayaran</h3>
                </div>
                <div class="table-container border-0 shadow-none rounded-none">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Metode & Bukti</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td>
                                        <div class="font-medium text-slate-700 dark:text-white">
                                            {{ $payment->paymentMethod->name ?? 'Deposit / Lainnya' }}
                                        </div>
                                        @if($payment->reference_number)
                                            <div class="text-xs text-slate-500">Ref: {{ $payment->reference_number }}</div>
                                        @endif
                                        
                                        @if($payment->proof_of_payment_path)
                                            <button type="button" onclick="viewProof('{{ asset('storage/' . $payment->proof_of_payment_path) }}')" 
                                                    class="inline-flex items-center gap-1 text-[10px] text-indigo-600 hover:text-indigo-800 hover:underline mt-1 transition-colors">
                                                <i class="material-icons text-[10px]">visibility</i> Lihat Bukti
                                            </button>
                                        @endif
                                    </td>
                                    <td class="font-medium">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @php
                                            $pClass = match($payment->status) {
                                                'completed' => 'text-emerald-600 bg-emerald-50',
                                                'pending_verification' => 'text-amber-600 bg-amber-50',
                                                'pending_clearance' => 'text-purple-600 bg-purple-50',
                                                'failed' => 'text-red-600 bg-red-50',
                                                default => 'text-slate-600 bg-slate-50'
                                            };
                                            $pLabel = match($payment->status) {
                                                'completed' => 'Diterima',
                                                'pending_verification' => 'Verifikasi',
                                                'pending_clearance' => 'Menunggu Kliring',
                                                'failed' => 'Ditolak',
                                                default => $payment->status
                                            };
                                        @endphp
                                        <span class="px-2 py-1 rounded text-xs font-bold {{ $pClass }}">
                                            {{ $pLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-slate-500 italic">Belum ada pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Payment Action & Info --}}
        <div class="lg:col-span-1 flex flex-col gap-6">
            
            {{-- Sales Info --}}
            <div class="card">
                <div class="card-body flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                        {{ substr($invoice->sales->name ?? 'S', 0, 1) }}
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-bold">Sales Contact</p>
                        <p class="font-bold text-slate-800 dark:text-white">
                            {{ $invoice->sales->name ?? 'Staff Sales' }}
                        </p>
                        <p class="text-xs text-slate-500">{{ $invoice->sales->phone_number ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- PAYMENT FORM --}}
            @if($invoice->remaining_balance > 100)
                <div class="card border-indigo-200 shadow-md" x-data="paymentForm()">
                    <div class="card-header bg-indigo-50/50 dark:bg-indigo-900/20 border-b border-indigo-100 dark:border-indigo-800">
                        <h3 class="card-header-title text-indigo-700 dark:text-indigo-400 flex items-center gap-2">
                            <i class="material-icons">payment</i> Lakukan Pembayaran
                        </h3>
                    </div>

                    {{-- Opsi Mode Pembayaran --}}
                    <div class="p-2 flex gap-2 justify-center border-b border-slate-100 dark:border-slate-700">
                        <button type="button" @click="mode = 'manual'" 
                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-colors"
                            :class="mode === 'manual' ? 'bg-indigo-600 text-white shadow' : 'bg-transparent text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                            Upload Bukti
                        </button>
                        
                        {{-- ✅ REVISI: Tampilkan hanya jika Gateway Aktif --}}
                        @if($gatewayMethod)
                            <button type="button" @click="mode = 'online'" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-colors"
                                :class="mode === 'online' ? 'bg-indigo-600 text-white shadow' : 'bg-transparent text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                                Online / QRIS
                            </button>
                        @endif
                    </div>

                    <div class="card-body">
                        
                        {{-- MODE: MANUAL TRANSFER --}}
                        <form x-show="mode === 'manual'" action="{{ route('client.invoices.uploadProof', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="space-y-4">
                                {{-- 1. Metode Pembayaran --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Metode Pembayaran <span class="text-red-500">*</span></label>
                                    <select name="payment_method_id" class="tom-select" x-model="selectedMethodId" required>
                                        <option value="">Pilih Metode...</option>
                                        @foreach($paymentMethods as $pm)
                                            <option value="{{ $pm->payment_method_id }}">{{ $pm->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 2. Bank Tujuan --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Tujuan Transfer <span class="text-red-500">*</span></label>
                                    <select name="company_bank_account_id" class="tom-select" x-model="selectedBankId" required>
                                        <option value="">Pilih Rekening Tujuan...</option>
                                        @foreach($companyBankAccounts as $bank)
                                            <option value="{{ $bank->company_bank_account_id }}">
                                                {{ $bank->bank_name }} - {{ $bank->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Detail Rekening --}}
                                <div x-show="selectedBank" x-transition class="p-3 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 rounded-lg">
                                    <p class="text-[10px] text-blue-600 dark:text-blue-400 uppercase font-bold mb-1">Silakan transfer ke:</p>
                                    <p class="text-sm font-bold text-slate-800 dark:text-white" x-text="selectedBank.bank_name"></p>
                                    <p class="text-base font-mono font-extrabold text-slate-900 dark:text-white tracking-wide" x-text="selectedBank.account_number"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="'a.n ' + selectedBank.account_name"></p>
                                </div>

                                {{-- Opsi Saldo --}}
                                @if(Auth::guard('client')->user()->balance > 0)
                                    <div class="p-3 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-100 dark:border-emerald-800">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="use_credit" value="1" x-model="useCredit" @change="calculateDue" class="form-checkbox rounded text-emerald-600 focus:ring-emerald-500">
                                            <div class="text-sm">
                                                <span class="font-bold text-emerald-800 dark:text-emerald-400">Gunakan Saldo Deposit</span>
                                                <div class="text-xs text-emerald-600">
                                                    Tersedia: Rp {{ number_format(Auth::guard('client')->user()->balance, 0, ',', '.') }}
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endif

                                {{-- Nominal --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Nominal Transfer <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input autonumeric" name="payment_amount" 
                                           x-model="paymentAmount" @input="calculateOverpayment" 
                                           placeholder="0" required data-an-synced="true">
                                    
                                    <div x-show="overpaymentAmount > 0" x-transition class="mt-2 p-2 bg-blue-50 text-blue-700 rounded text-xs border border-blue-100">
                                        <i class="material-icons text-[12px] align-middle">info</i>
                                        Sisa <strong>Rp <span x-text="formatRupiah(overpaymentAmount)"></span></strong> akan masuk ke Saldo Deposit Anda.
                                    </div>
                                </div>

                                {{-- Bukti & Ref --}}
                                <div x-show="showProofInput" x-transition>
                                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Bukti Transfer (Foto) <span class="text-red-500">*</span></label>
                                    <input type="file" name="proof_of_payment" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*" :required="showProofInput">
                                </div>

                                <div x-show="showReferenceInput" x-transition>
                                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">No. Referensi / Berita <span class="text-red-500">*</span></label>
                                    <input type="text" name="reference_number" class="form-input text-sm" placeholder="Contoh: TRANSFER #INV-001" :required="showReferenceInput">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Catatan (Opsional)</label>
                                    <textarea name="notes" rows="2" class="form-input text-sm" placeholder="Catatan tambahan..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary w-full justify-center">
                                    <i class="material-icons text-sm">upload</i> Kirim Konfirmasi
                                </button>
                            </div>
                        </form>

                        {{-- MODE: ONLINE (MIDTRANS) - Hanya dirender jika gateway aktif --}}
                        @if($gatewayMethod)
                            <div x-show="mode === 'online'" class="text-center py-4">
                                <i class="material-icons text-4xl text-slate-300 mb-2">qr_code_scanner</i>
                                <p class="text-sm text-slate-500 mb-4">
                                    Bayar instan menggunakan QRIS, Virtual Account, atau E-Wallet. Verifikasi otomatis.
                                </p>
                                
                                <div x-data="midtransPayment('{{ $invoice->invoice_id }}', {{ $invoice->remaining_balance }})">
                                    @if(Auth::guard('client')->user()->balance > 0)
                                        <div class="mb-4 text-left p-3 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-100">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" x-model="useCreditOnline" class="form-checkbox rounded text-emerald-600">
                                                <span class="text-sm font-bold text-emerald-800 dark:text-emerald-400">Gunakan Saldo (Rp {{ number_format(Auth::guard('client')->user()->balance, 0, ',', '.') }})</span>
                                            </label>
                                        </div>
                                    @endif

                                    <div class="mb-4">
                                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1 text-left">Total Bayar</label>
                                        <input type="text" x-model="payAmount" class="form-input text-center font-bold text-lg bg-gray-50" readonly>
                                    </div>

                                    <button @click="payNow" class="btn btn-primary w-full justify-center" :class="{'is-loading': loading}">
                                        Bayar Sekarang (Online)
                                    </button>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ✅ MODAL PREVIEW GAMBAR --}}
    <div id="imagePreviewModal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" onclick="closeImageModal()"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl dark:bg-slate-800">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Bukti Pembayaran</h3>
                        <button type="button" onclick="closeImageModal()" class="text-slate-400 hover:text-slate-500">
                            <i class="material-icons">close</i>
                        </button>
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-900 p-4 flex justify-center items-center">
                        <img id="previewImageSrc" src="" class="max-h-[80vh] w-auto rounded shadow-sm object-contain">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- Hanya load script midtrans jika gateway aktif --}}
    @if($gatewayMethod)
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
    @endif
    <script>
        // Modal Logic
        function viewProof(url) {
            document.getElementById('previewImageSrc').src = url;
            document.getElementById('imagePreviewModal').classList.remove('hidden');
        }
        function closeImageModal() {
            document.getElementById('imagePreviewModal').classList.add('hidden');
            setTimeout(() => { document.getElementById('previewImageSrc').src = ''; }, 300);
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentForm', () => ({
                mode: 'manual', 
                useCredit: false,
                paymentAmount: '', 
                selectedMethodId: '',
                selectedBankId: '',
                overpaymentAmount: 0,
                
                // Config Mapping
                methodConfigs: {
                    @foreach($paymentMethods as $pm)
                        '{{ $pm->payment_method_id }}': '{{ $pm->client_input_config }}',
                    @endforeach
                },
                
                // Bank Accounts
                bankAccounts: {
                    @foreach($companyBankAccounts as $bank)
                        '{{ $bank->company_bank_account_id }}': {
                            bank_name: '{{ $bank->bank_name }}',
                            account_name: '{{ $bank->account_name }}',
                            account_number: '{{ $bank->account_number }}'
                        },
                    @endforeach
                },

                // Saldo & Tagihan
                clientBalance: {{ Auth::guard('client')->user()->balance }},
                remainingInvoice: {{ $invoice->remaining_balance }},

                get currentConfig() { return this.methodConfigs[this.selectedMethodId] || 'none'; },
                get showProofInput() { return ['proof_only', 'proof_and_reference'].includes(this.currentConfig); },
                get showReferenceInput() { return ['reference_only', 'proof_and_reference'].includes(this.currentConfig); },
                get selectedBank() { return this.bankAccounts[this.selectedBankId] || null; },

                calculateOverpayment() {
                    let input = parseFloat(this.paymentAmount.replace(/\./g, '').replace(',', '.')) || 0;
                    let credit = this.useCredit ? Math.min(this.clientBalance, this.remainingInvoice) : 0;
                    let cashNeeded = Math.max(0, this.remainingInvoice - credit);
                    this.overpaymentAmount = Math.max(0, input - cashNeeded);
                },

                calculateDue() { this.calculateOverpayment(); },

                formatRupiah(number) {
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number);
                }
            }));

            // Midtrans Logic (Hanya jika gateway ada)
            @if($gatewayMethod)
            Alpine.data('midtransPayment', (invoiceId, maxAmount) => ({
                loading: false, useCreditOnline: false, invoiceId: invoiceId, originalAmount: maxAmount,
                get payAmount() { 
                    // Jika pakai kredit, kurangi jumlah bayar online
                    let amountToPay = this.originalAmount;
                    if(this.useCreditOnline) {
                        let credit = {{ Auth::guard('client')->user()->balance }};
                        amountToPay = Math.max(0, amountToPay - credit);
                    }
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amountToPay); 
                },
                payNow() {
                    this.loading = true;
                    fetch(`{{ route('client.invoices.pay', ':id') }}`.replace(':id', this.invoiceId), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ amount: this.originalAmount, use_credit: this.useCreditOnline })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.loading = false;
                        if(data.snap_token) { window.snap.pay(data.snap_token, { onSuccess: () => window.location.reload(), onPending: () => window.location.reload(), onError: () => showToast('Gagal', 'error') }); }
                        else if(data.status === 'paid_by_credit') { window.location.reload(); }
                        else { showToast(data.message, 'error'); }
                    })
                    .catch(() => { this.loading = false; showToast('Gagal memproses.', 'error'); });
                }
            }));
            @endif
        });
    </script>
    @endpush

@endsection
@extends('admin.layouts.app')

@section('title', 'Detail Faktur Penjualan')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        {{-- =====================================================================
             1. HEADER & ACTION BUTTONS
             ===================================================================== --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.invoices.index') }}" 
                       class="w-9 h-9 rounded-full flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 transition-colors shadow-sm dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 group">
                        <i class="material-icons text-lg leading-none group-hover:-translate-x-0.5 transition-transform">arrow_back</i>
                    </a>
                    <h1 class="page-title">{{ $invoice->invoice_number }}</h1>
                </div>
                <div class="flex items-center gap-2 mt-1 ml-[52px]">
                    <span class="text-sm text-slate-500">Tanggal: {{ $invoice->order_date->format('d M Y') }}</span>
                    <span class="text-slate-300">•</span>
                    @php
                        $statusClass = match($invoice->status) {
                            'paid' => 'badge-success',
                            'partially_paid' => 'badge-info',
                            'draft' => 'badge-secondary',
                            'cancelled' => 'badge-danger',
                            default => 'badge-warning'
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>
            
            <div class="flex gap-2 ml-[52px] sm:ml-0">
                @if($invoice->status == 'draft')
                    <a href="{{ route('admin.invoices.edit', $invoice->invoice_id) }}" class="btn btn-secondary">
                        <i class="material-icons text-sm mr-1">edit</i> Edit
                    </a>
                    
                    {{-- Tombol Posting --}}
                    <button type="button" onclick="confirmPosting()" class="btn btn-primary">
                        <i class="material-icons text-sm mr-1">check_circle</i> Posting
                    </button>
                    <form id="form-posting" action="{{ route('admin.invoices.confirm', $invoice->invoice_id) }}" method="POST" class="hidden">
                        @csrf
                    </form>
                @else
                    <a href="{{ route('admin.invoices.pdf', $invoice->invoice_id) }}" class="btn btn-secondary" target="_blank">
                        <i class="material-icons text-sm mr-1">print</i> Cetak PDF
                    </a>

                    @if(!in_array($invoice->status, ['paid', 'cancelled']))
                         <button type="button" onclick="confirmCancel()" class="btn btn-danger">
                            <i class="material-icons text-sm mr-1">cancel</i> Batalkan
                        </button>
                        <form id="form-cancel" action="{{ route('admin.invoices.cancel', $invoice->invoice_id) }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    @endif
                    
                    @if($invoice->status != 'cancelled')
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false" class="btn btn-secondary">
                                <i class="material-icons text-sm mr-1">more_vert</i> Koreksi
                            </button>
                            <div x-show="open" x-transition class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-100 dark:border-slate-700 z-50 py-1" style="display: none;">
                                <a href="{{ route('admin.invoice-adjustments.create.manual', $invoice->invoice_id) }}" class="flex items-center px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                    <div class="w-6 flex justify-center mr-2">
                                        <i class="material-icons text-base text-slate-400">tune</i>
                                    </div>
                                    <span>Penyesuaian Manual</span>
                                </a>
                                <a href="{{ route('admin.invoice-adjustments.create.auto', $invoice->invoice_id) }}" class="flex items-center px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                    <div class="w-6 flex justify-center mr-2">
                                        <i class="material-icons text-base text-slate-400">auto_fix_high</i>
                                    </div>
                                    <span>Revisi Otomatis</span>
                                </a>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- =====================================================================
             2. INFORMASI UTAMA & RINGKASAN TAGIHAN
             ===================================================================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            
            {{-- Kartu Kiri: Info Klien --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-header-title">Informasi Pelanggan</h3>
                </div>
                <div class="card-body">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            {{ substr($invoice->client->client_name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 dark:text-white">{{ $invoice->client->client_name }}</div>
                            <div class="text-sm text-slate-500">{{ $invoice->client->phone_number ?? '-' }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ $invoice->client->address ?? 'Alamat tidak tersedia' }}</div>
                        </div>
                    </div>
                    <hr class="my-4 border-slate-100 dark:border-slate-700">
                    
                    {{-- Info Saldo Deposit --}}
                    <div class="flex justify-between items-center text-sm mb-2 p-2 bg-indigo-50 rounded-lg">
                        <span class="text-indigo-800">Saldo Deposit Tersedia:</span>
                        <span class="font-bold text-indigo-700">Rp {{ number_format($invoice->client->balance, 0, ',', '.') }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm mt-3">
                        <div>
                            <span class="block text-xs text-slate-400 uppercase">Sales</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $invoice->sales->full_name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 uppercase">Jatuh Tempo</span>
                            <span class="font-medium {{ $invoice->due_date < now() && $invoice->status != 'paid' ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $invoice->due_date->format('d M Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu Kanan: Ringkasan Angka --}}
            <div class="card lg:col-span-2">
                <div class="card-header">
                    <h3 class="card-header-title">Ringkasan Tagihan</h3>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div class="text-xs text-slate-500 uppercase font-bold mb-1">Total Tagihan</div>
                            <div class="text-lg font-bold text-slate-800 dark:text-white">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800">
                            <div class="text-xs text-emerald-600 uppercase font-bold mb-1">Sudah Dibayar</div>
                            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-400">
                                Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-800">
                            <div class="text-xs text-rose-600 uppercase font-bold mb-1">Sisa Tagihan</div>
                            <div class="text-xl font-extrabold text-rose-600 dark:text-rose-400">
                                Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                    
                    @php
                        $selisihPembayaran = $invoice->total_due - $invoice->amount_paid;
                    @endphp

                    @if($selisihPembayaran < -0.01)
                        <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3 animate-fade-in-up">
                            <div class="p-2 bg-emerald-100 rounded-full shrink-0 text-emerald-600">
                                <i class="material-icons text-xl">savings</i>
                            </div>
                            <div>
                                <h4 class="font-bold text-emerald-800 text-sm uppercase tracking-wide mb-1">
                                    Status: Kelebihan Pembayaran (Overpayment)
                                </h4>
                                <p class="text-sm text-emerald-700 leading-relaxed">
                                    Terdapat kelebihan bayar sebesar 
                                    <span class="font-bold font-mono text-base">Rp {{ number_format(abs($selisihPembayaran), 0, ',', '.') }}</span>.
                                    <br>
                                    Sistem telah otomatis mengamankan dana ini ke dalam 
                                    <a href="{{ route('admin.clients.show', $invoice->client_id) }}" class="underline hover:text-emerald-900 font-bold" target="_blank">Saldo Kredit (Deposit)</a> 
                                    klien <strong>{{ $invoice->client->client_name }}</strong>.
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($invoice->adjustments->count() > 0 || $invoice->returns->count() > 0)
                        <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-lg flex items-center gap-2 text-xs text-amber-800">
                            <i class="material-icons text-sm text-amber-500">info</i>
                            <span>Tagihan ini memiliki riwayat <strong>Penyesuaian</strong> atau <strong>Retur</strong>. Cek tab di bawah.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- =====================================================================
             3. TABEL RINCIAN BARANG
             ===================================================================== --}}
        <div class="card mb-6">
            <div class="card-header bg-slate-50 dark:bg-slate-800">
                <h3 class="card-header-title">Rincian Barang</h3>
            </div>
            <div class="overflow-x-auto w-full">
                <table class="table-modern w-full min-w-[600px]">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-right">Harga</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>
                                    <div class="font-bold text-slate-700 dark:text-slate-200">{{ $item->product->product_name ?? 'Item Dihapus' }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $item->product->product_code ?? '-' }}</div>
                                </td>
                                <td class="text-right font-mono text-slate-600 dark:text-slate-300">
                                    Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td class="text-center font-bold">
                                    {{ number_format($item->quantity, 0, ',', '.') }}
                                    @if($item->quantity_returned > 0)
                                        <span class="text-xs text-rose-500 block">(-{{ $item->quantity_returned }} Retur)</span>
                                    @endif
                                </td>
                                <td class="text-right font-bold text-slate-800 dark:text-white">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 text-sm">
                        <tr>
                            <td colspan="3" class="px-6 py-2 text-right font-bold text-slate-500">Subtotal Item</td>
                            <td class="px-6 py-2 text-right font-mono font-bold">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @foreach($invoice->additionalCosts as $cost)
                            <tr>
                                <td colspan="3" class="px-6 py-1 text-right text-slate-500 italic">{{ $cost->description }}</td>
                                <td class="px-6 py-1 text-right font-mono text-indigo-600">+ Rp {{ number_format($cost->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        @if($invoice->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="px-6 py-1 text-right text-slate-500">Diskon Global ({{ $invoice->discount_percentage }}%)</td>
                                <td class="px-6 py-1 text-right font-mono text-rose-500">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @foreach($invoice->taxes as $tax)
                            <tr>
                                <td colspan="3" class="px-6 py-1 text-right text-slate-500">{{ $tax->name }} ({{ $tax->rate }}%)</td>
                                <td class="px-6 py-1 text-right font-mono text-emerald-600">+ Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-100 dark:bg-slate-900 border-t-2 border-slate-300 dark:border-slate-600">
                            <td colspan="3" class="px-6 py-4 text-right font-extrabold uppercase text-slate-600 dark:text-slate-300">Grand Total</td>
                            <td class="px-6 py-4 text-right font-extrabold text-xl text-indigo-600 dark:text-indigo-400">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- =====================================================================
             4. TABS & FORM PEMBAYARAN
             ===================================================================== --}}
        <div x-data="{ activeTab: 'payments' }" class="mb-10">
            
            {{-- Tab Navigation --}}
            <div class="flex gap-2 mb-4 border-b border-slate-200 dark:border-slate-700">
                <button @click="activeTab = 'payments'" 
                        :class="activeTab === 'payments' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2 border-b-2 font-bold text-sm transition-colors flex items-center gap-2">
                    <i class="material-icons text-sm">payments</i> Pembayaran
                </button>
                <button @click="activeTab = 'adjustments'" 
                        :class="activeTab === 'adjustments' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2 border-b-2 font-bold text-sm transition-colors flex items-center gap-2">
                    <i class="material-icons text-sm">tune</i> Penyesuaian ({{ $invoice->adjustments->count() }})
                </button>
                <button @click="activeTab = 'returns'" 
                        :class="activeTab === 'returns' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-2 border-b-2 font-bold text-sm transition-colors flex items-center gap-2">
                    <i class="material-icons text-sm">assignment_return</i> Retur ({{ $invoice->returns->count() }})
                </button>
            </div>

            {{-- TAB CONTENT: PEMBAYARAN --}}
            <div x-show="activeTab === 'payments'" x-transition.opacity>
                
                {{-- Form Catat Pembayaran Baru (Hanya jika belum lunas & bukan draft) --}}
                @if($invoice->remaining_balance > 0 && !in_array($invoice->status, ['draft', 'cancelled']))
                    <div class="card mb-6 border border-indigo-100 bg-indigo-50/30 dark:bg-slate-800 dark:border-slate-700">
                        <div class="card-header py-3 bg-transparent flex justify-between items-center">
                            <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-400">Catat Pembayaran Baru (Internal)</h4>
                            
                            {{-- ✅ FIX ALPINE ERROR: LOGIKA INLINE --}}
@if(isset($gatewayMethod) && $gatewayMethod)
                                <div x-data="{
                                    loading: false,
                                    invoiceId: '{{ $invoice->invoice_id }}',
                                    originalAmount: {{ (float) $invoice->remaining_balance }},
                                    
                                    payNow() {
                                        window.confirmDialog({
                                            title: 'Proses Pembayaran Midtrans?',
                                            text: 'Anda akan diarahkan ke popup pembayaran untuk Invoice ini.',
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonText: 'Ya, Lanjut',
                                            cancelButtonText: 'Batal'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                this.executeMidtrans();
                                            }
                                        });
                                    },

                                    executeMidtrans() {
                                        this.loading = true;
                                        // ✅ REVISI: Gunakan route ADMIN, bukan Client
                                        fetch('{{ route('admin.midtrans.pay', $invoice->invoice_id) }}', {
                                            method: 'POST',
                                            headers: { 
                                                'Content-Type': 'application/json', 
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content 
                                            },
                                            body: JSON.stringify({ 
                                                amount: this.originalAmount, 
                                                use_credit: false 
                                            })
                                        })
                                        .then(r => {
                                            // Cek jika response bukan JSON (misal HTML error page)
                                            const contentType = r.headers.get('content-type');
                                            if (!contentType || !contentType.includes('application/json')) {
                                                throw new Error('Server Error: Respon bukan JSON (Mungkin error 500/404/403).');
                                            }
                                            return r.json().then(data => ({ status: r.status, body: data }));
                                        })
                                        .then(({ status, body }) => {
                                            this.loading = false;
                                            if (status >= 400) {
                                                window.showToast(body.message || 'Terjadi kesalahan pada server.', 'error');
                                                return;
                                            }
                                            
                                            if(body.snap_token) { 
                                                window.snap.pay(body.snap_token, { 
                                                    onSuccess: () => window.location.reload(), 
                                                    onPending: () => window.location.reload(), 
                                                    onError: () => window.showToast('Gagal', 'error') 
                                                }); 
                                            } else { 
                                                window.showToast(body.message, 'success'); 
                                                setTimeout(() => window.location.reload(), 1000);
                                            }
                                        })
                                        .catch((err) => { 
                                            this.loading = false; 
                                            console.error(err);
                                            window.showToast('Gagal memproses: ' + err.message, 'error'); 
                                        });
                                    }
                                }">
                                    <button type="button" @click="payNow()" class="btn btn-sm btn-secondary text-xs flex items-center gap-1" :class="{'is-loading': loading}" :disabled="loading">
                                        <i class="material-icons text-xs">qr_code</i> Bayar Online (Midtrans)
                                    </button>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.payments.store', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                                    <div class="md:col-span-1">
                                        <label class="form-label text-[10px]">Tanggal Bayar</label>
                                        <input type="date" name="payment_date" class="form-input text-sm" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="form-label text-[10px]">Total Bayar (Rp)</label>
                                        <input type="text" name="amount" id="inputTotalPay" 
                                               class="form-input text-right font-bold text-emerald-600 autonumeric" 
                                               value="{{ $invoice->remaining_balance }}" required>
                                    </div>

                                    {{-- CHECKBOX GUNAKAN KREDIT --}}
                                    <div class="md:col-span-2 flex flex-col justify-center pt-5">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" name="use_credit" id="use_credit" class="form-checkbox rounded text-indigo-600 w-5 h-5" value="1" 
                                                @if($invoice->client->balance <= 0) disabled @endif>
                                            <label for="use_credit" class="text-sm text-slate-700 font-medium cursor-pointer select-none">
                                                Gunakan Deposit Pelanggan
                                                <span class="text-slate-400 text-xs block font-normal">
                                                    Saldo tersedia: <strong>Rp {{ number_format($invoice->client->balance, 0, ',', '.') }}</strong>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    {{-- RINCIAN ALOKASI (MUNCUL OTOMATIS) --}}
                                    <div class="md:col-span-4 bg-white p-3 rounded-lg border border-slate-200" id="allocationDetails" style="display:none;">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <span class="block text-xs text-slate-400">Diambil dari Deposit</span>
                                                <span class="font-bold text-indigo-600" id="dispCreditUsed">Rp 0</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs text-slate-400">Sisa Bayar (Transfer/Cash)</span>
                                                <span class="font-bold text-emerald-600" id="dispCashNeeded">Rp 0</span>
                                            </div>
                                            <div>
                                                <span class="block text-xs text-slate-400">Status</span>
                                                <span id="dispStatus" class="badge badge-secondary text-[10px]">Menunggu Input</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- BAGIAN METODE BAYAR (HANYA MUNCUL JIKA SISA CASH > 0) --}}
                                    <div class="md:col-span-4 grid grid-cols-1 md:grid-cols-4 gap-4" id="cashPaymentFields">
                                        <div class="md:col-span-1">
                                            <label class="form-label text-[10px]">Metode Bayar (Sisa)</label>
                                            <select name="payment_method_id" id="paymentMethodSelect" class="tom-select">
                                                <option value="">Pilih...</option>
                                                @foreach($paymentMethods as $method)
                                                    {{-- ✅ PENTING: Gunakan 'internal_input_config' karena ini halaman Admin --}}
                                                    <option value="{{ $method->payment_method_id }}" 
                                                            data-config="{{ $method->internal_input_config }}"
                                                            data-type="{{ $method->type }}">
                                                        {{ $method->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="md:col-span-1">
                                            <label class="form-label text-[10px]">Masuk ke Akun</label>
                                            <select name="company_bank_account_id" id="bankAccountSelect" class="tom-select">
                                                <option value="">Pilih Rekening...</option>
                                                @foreach($companyBankAccounts as $bank)
                                                    <option value="{{ $bank->company_bank_account_id }}">{{ $bank->bank_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="md:col-span-1 hidden" id="fieldReference">
                                            <label class="form-label text-[10px]" id="labelReference">No. Referensi</label>
                                            <input type="text" name="reference_number" id="inputReference" class="form-input text-sm" placeholder="Contoh: TRF-123">
                                        </div>

                                        {{-- ✅ PERBAIKAN: FLOWBITE DROPZONE FOR UPLOAD --}}
                                        <div class="md:col-span-1 hidden" id="fieldProof">
                                            <label class="form-label text-[10px]" id="labelProof">Bukti Transfer</label>
                                            
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
                                        <input type="text" name="notes" class="form-input text-sm" placeholder="Catatan tambahan...">
                                    </div>

                                    <div class="md:col-span-4 mt-2">
                                        {{-- ALERT REAL-TIME OVERPAYMENT --}}
                                        <div id="dynamicOverpaymentAlert" class="p-3 bg-blue-50 border border-blue-200 rounded-lg hidden transition-all mb-3">
                                            <div class="flex gap-2">
                                                <i class="material-icons text-blue-500 text-sm mt-0.5">info</i>
                                                <div class="text-xs text-blue-700">
                                                    <span class="font-bold">Info:</span> Nominal melebihi tagihan. 
                                                    Kelebihan <span id="excessAmountDisplay" class="font-bold font-mono">Rp 0</span> 
                                                    akan disimpan otomatis ke <strong>Deposit Pelanggan</strong>.
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-full justify-center">
                                            <i class="material-icons text-sm mr-1">save</i> Simpan Pembayaran
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Tabel Riwayat Pembayaran --}}
                <div class="table-container">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Metode / Info</th>
                                <th>Referensi & Bukti</th>
                                <th class="text-right">Nominal</th>
                                <th class="text-center">Status</th>
                                <th class="text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->payments as $payment)
                                <tr>
                                    <td class="text-sm align-top">
                                        {{ $payment->payment_date->format('d M Y') }}
                                        <div class="text-[10px] text-slate-400 mt-1">
                                            ID: #{{ $payment->payment_id }}
                                        </div>
                                    </td>
                                    <td class="align-top">
                                        {{-- Metode --}}
                                        <div class="font-bold text-slate-700 dark:text-slate-200 text-xs">
                                            {{ $payment->paymentMethod->name ?? 'Manual/Kredit' }}
                                        </div>
                                        
                                        {{-- Akun Bank --}}
                                        @if($payment->companyBankAccount)
                                            <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                                <i class="material-icons text-[10px]">account_balance</i>
                                                {{ $payment->companyBankAccount->bank_name }}
                                            </div>
                                        @endif

                                        {{-- Diterima Oleh --}}
                                        @if($payment->receivedBy)
                                            <div class="text-[10px] text-slate-400 mt-1">
                                                Oleh: {{ $payment->receivedBy->full_name }}
                                            </div>
                                        @endif

                                        {{-- Catatan --}}
                                        @if($payment->notes)
                                            <div class="text-[10px] text-slate-500 italic mt-1 border-t border-slate-100 pt-1">
                                                "{{ $payment->notes }}"
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-top">
                                        {{-- Referensi --}}
                                        @if($payment->reference_number)
                                            <div class="text-xs font-mono text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded inline-block mb-1">
                                                {{ $payment->reference_number }}
                                            </div>
                                        @else
                                            <div class="text-xs text-slate-400">-</div>
                                        @endif

                                        {{-- Tombol Lihat Bukti --}}
                                        @if($payment->proof_of_payment_path)
                                            <div class="mt-1">
                                                <button onclick="showProof('{{ asset('storage/' . $payment->proof_of_payment_path) }}')" class="text-indigo-600 hover:text-indigo-800 text-xs flex items-center gap-1 underline decoration-dashed">
                                                    <i class="material-icons text-[12px]">image</i> Lihat Bukti
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold text-emerald-600 align-top">
                                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center align-top">
                                        @php
                                            $badgeClass = match($payment->status) {
                                                'completed' => 'badge-success',
                                                'pending_verification' => 'badge-warning',
                                                'pending_clearance' => 'badge-info',
                                                'failed' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} text-[10px]">
                                            {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-center align-top">
                                        <div class="flex items-center justify-center gap-1">
                                            
                                            {{-- TOMBOL APPROVE/REJECT (Hanya jika status pending) --}}
                                            @if($payment->status == 'pending_verification' || $payment->status == 'pending_clearance')
                                                <button onclick="confirmApprove('{{ $payment->payment_id }}')" class="btn-icon btn-sm btn-success text-white bg-emerald-500 hover:bg-emerald-600 shadow-sm" title="Setujui">
                                                    <i class="material-icons text-sm">check</i>
                                                </button>
                                                <form id="form-approve-{{ $payment->payment_id }}" action="{{ route('admin.payments.approve', $payment->payment_id) }}" method="POST" class="hidden">
                                                    @csrf @method('POST')
                                                </form>

                                                <button onclick="confirmReject('{{ $payment->payment_id }}')" class="btn-icon btn-sm btn-danger text-white bg-rose-500 hover:bg-rose-600 shadow-sm" title="Tolak">
                                                    <i class="material-icons text-sm">close</i>
                                                </button>
                                                <form id="form-reject-{{ $payment->payment_id }}" action="{{ route('admin.payments.reject', $payment->payment_id) }}" method="POST" class="hidden">
                                                    @csrf @method('POST')
                                                </form>
                                            @endif
                                            
                                            {{-- TOMBOL HAPUS (Rollback) --}}
                                            <button onclick="confirmDeletePayment('{{ $payment->payment_id }}')" class="btn-icon btn-sm btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50" title="Hapus">
                                                <i class="material-icons text-sm">delete</i>
                                            </button>
                                            <form id="delete-payment-{{ $payment->payment_id }}" action="{{ route('admin.payments.destroy', $payment->payment_id) }}" method="POST" class="hidden">
                                                @csrf @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center p-4 text-slate-400 text-sm">Belum ada riwayat pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB CONTENT: ADJUSTMENTS --}}
            <div x-show="activeTab === 'adjustments'" x-transition.opacity style="display: none;">
                <div class="table-container">
                    <table class="table-modern">
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
                            @forelse($invoice->adjustments as $adj)
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
                                        <form id="delete-adj-{{ $adj->adjustment_id }}" action="{{ route('admin.invoice-adjustments.destroy', $adj->adjustment_id) }}" method="POST" class="hidden">
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

            {{-- TAB CONTENT: RETURNS --}}
            <div x-show="activeTab === 'returns'" x-transition.opacity style="display: none;">
                <div class="table-container">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>No. Retur</th>
                                <th>Tanggal</th>
                                <th>Tipe Penanganan</th>
                                <th class="text-right">Nilai Retur</th>
                                <th class="text-center w-24">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->returns as $ret)
                                <tr>
                                    <td class="font-bold text-indigo-600 text-sm">{{ $ret->return_number }}</td>
                                    <td class="text-sm">{{ $ret->return_date->format('d M Y') }}</td>
                                    <td>
                                        @if($ret->return_handling_type == 'deduct_invoice')
                                            <span class="badge badge-primary text-[10px]">Potong Tagihan</span>
                                        @else
                                            <span class="badge badge-secondary text-[10px]">Simpan Kredit</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-bold text-slate-700">Rp {{ number_format($ret->total_amount, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.sales-returns.show', $ret->return_id) }}" class="btn-icon btn-sm btn-secondary" target="_blank">
                                            <i class="material-icons text-sm">open_in_new</i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center p-4 text-slate-400 text-sm">Tidak ada retur barang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        @if($invoice->notes)
            <div class="card bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <div class="card-body p-4 text-sm text-slate-600 dark:text-slate-300 italic">
                    <span class="font-bold not-italic text-xs block mb-1">Catatan Faktur:</span>
                    {{ $invoice->notes }}
                </div>
            </div>
        @endif
        
        {{-- =====================================================================
             5. IMAGE MODAL (KHUSUS UNTUK SHOW PROOF)
             ===================================================================== --}}
        <div id="imageModal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-[60] hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="relative w-full max-w-4xl max-h-full">
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                    <div class="flex items-center justify-between p-4 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Bukti Pembayaran
                        </h3>
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
{{-- ✅ 1. SCRIPT MIDTRANS (Hanya jika Gateway Aktif) --}}
@if(isset($gatewayMethod) && $gatewayMethod)
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. VARIABLES ---
        const inputAmount = document.getElementById('inputTotalPay');
        const checkboxCredit = document.getElementById('use_credit');
        
        // Element tampilan Alokasi
        const divAllocation = document.getElementById('allocationDetails');
        const dispCreditUsed = document.getElementById('dispCreditUsed');
        const dispCashNeeded = document.getElementById('dispCashNeeded');
        const dispStatus = document.getElementById('dispStatus');
        
        // Container dropdown bank
        const cashPaymentFields = document.getElementById('cashPaymentFields');
        
        // Input dinamis metode bayar
        const methodSelect = document.getElementById('paymentMethodSelect');
        const bankSelect = document.getElementById('bankAccountSelect');
        
        // Alert Overpayment
        const alertBox = document.getElementById('dynamicOverpaymentAlert');
        const excessDisplay = document.getElementById('excessAmountDisplay');
        
        // Data dari PHP
        const remainingInvoice = {{ $invoice->remaining_balance }};
        const clientBalance = {{ $invoice->client->balance }};
        
        // File Input Label Logic (Flowbite)
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

        // --- 2. FUNGSI KALKULASI UTAMA ---
        function calculatePayment() {
            if (!inputAmount) return; 

            // Ambil Nilai Input (Total yang ingin dibayar user hari ini)
            let totalPay = 0;
            if (AutoNumeric.getAutoNumericElement(inputAmount)) {
                totalPay = parseFloat(AutoNumeric.getAutoNumericElement(inputAmount).getNumericString() || 0);
            } else {
                totalPay = parseFloat(inputAmount.value.replace(/\./g, '').replace(',', '.') || 0);
            }

            // Cek penggunaan kredit
            let creditUsed = 0;
            if (checkboxCredit && checkboxCredit.checked) {
                creditUsed = Math.min(clientBalance, remainingInvoice, totalPay);
            }

            // Hitung Sisa Cash yang dibutuhkan
            let cashNeeded = Math.max(0, totalPay - creditUsed);

            // --- UPDATE UI ALOKASI ---
            if (checkboxCredit && checkboxCredit.checked) {
                divAllocation.style.display = 'block';
                dispCreditUsed.innerText = formatRupiah(creditUsed);
                dispCashNeeded.innerText = formatRupiah(cashNeeded);
                
                if (creditUsed > 0 && cashNeeded <= 0.01) {
                    dispStatus.innerText = "Lunas dengan Deposit";
                    dispStatus.className = "badge badge-success text-[10px]";
                } else if (creditUsed > 0 && cashNeeded > 0) {
                    dispStatus.innerText = "Split Payment (Deposit + Cash)";
                    dispStatus.className = "badge badge-info text-[10px]";
                } else {
                    dispStatus.innerText = "Menunggu Input Nominal";
                    dispStatus.className = "badge badge-secondary text-[10px]";
                }
            } else {
                divAllocation.style.display = 'none';
            }

            // --- SHOW/HIDE DROPDOWN BANK ---
            if (cashNeeded > 0.01) {
                cashPaymentFields.classList.remove('hidden');
                cashPaymentFields.style.display = 'grid'; 
                
                methodSelect.setAttribute('required', 'required');
                bankSelect.setAttribute('required', 'required');
            } else {
                cashPaymentFields.classList.add('hidden');
                cashPaymentFields.style.display = 'none';
                
                methodSelect.removeAttribute('required');
                bankSelect.removeAttribute('required');
                
                if (methodSelect.tomselect) methodSelect.tomselect.clear();
                if (bankSelect.tomselect) bankSelect.tomselect.clear();
            }

            // --- ALERT OVERPAYMENT REALTIME ---
            if (totalPay > remainingInvoice + 1) { // Toleransi 1 perak
                const excess = totalPay - remainingInvoice;
                excessDisplay.innerText = formatRupiah(excess);
                alertBox.classList.remove('hidden');
            } else {
                alertBox.classList.add('hidden');
            }
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        // --- EVENT LISTENERS ---
        if (inputAmount) {
            inputAmount.addEventListener('keyup', calculatePayment);
            inputAmount.addEventListener('change', calculatePayment);
            inputAmount.addEventListener('autoNumeric:rawValueModified', calculatePayment);
        }
        
        if (checkboxCredit) {
            checkboxCredit.addEventListener('change', calculatePayment);
        }

        // --- 3. LOGIC BAWAAN (Dynamic Fields Metode Bayar) ---
        const fieldRef = document.getElementById('fieldReference');
        const inputRef = document.getElementById('inputReference');
        const labelRef = document.getElementById('labelReference');
        const fieldProof = document.getElementById('fieldProof');
        const inputProof = document.getElementById('inputProof');
        const labelProof = document.getElementById('labelProof');

        if (methodSelect) {
            methodSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const config = selectedOption.getAttribute('data-config');
                
                fieldRef.classList.add('hidden');
                fieldProof.classList.add('hidden');
                inputRef.removeAttribute('required');
                inputProof.removeAttribute('required');
                labelRef.classList.remove('label-required');
                labelProof.classList.remove('label-required');

                if (config === 'proof_only' || config === 'proof_and_reference') {
                    fieldProof.classList.remove('hidden');
                    inputProof.setAttribute('required', 'required');
                    labelProof.classList.add('label-required');
                }
                if (config === 'reference_only' || config === 'proof_and_reference') {
                    fieldRef.classList.remove('hidden');
                    inputRef.setAttribute('required', 'required');
                    labelRef.classList.add('label-required');
                }
            });
        }
        
        // Jalankan kalkulasi awal saat halaman load
        calculatePayment();
    });

    // SWEETALERT CONFIRMATIONS
    function confirmPosting() { 
        window.confirmDialog({ 
            title: 'Konfirmasi Invoice?', 
            text: "Stok akan dikurangi dan jurnal penjualan akan diposting.", 
            icon: 'question', 
            showCancelButton: true, 
            confirmButtonColor: '#4f46e5', 
            confirmButtonText: 'Ya, Posting!', 
            cancelButtonText: 'Batal' 
        }).then((r) => { 
            if(r.isConfirmed) document.getElementById('form-posting').submit(); 
        }); 
    }

    function confirmCancel() { 
        window.confirmDialog({ 
            title: 'Batalkan Invoice?', 
            text: "Status akan berubah menjadi Cancelled. Jurnal pembalik akan dibuat dan stok dikembalikan.", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#ef4444', 
            confirmButtonText: 'Ya, Batalkan!', 
            cancelButtonText: 'Kembali' 
        }).then((r) => { 
            if(r.isConfirmed) document.getElementById('form-cancel').submit(); 
        }); 
    }

    function confirmDeletePayment(id) { 
        window.confirmDialog({ 
            title: 'Hapus Pembayaran?', 
            text: "Jurnal pembayaran akan dibalik (reversal).", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#ef4444', 
            confirmButtonText: 'Ya, Hapus!', 
            cancelButtonText: 'Batal' 
        }).then((r) => { 
            if(r.isConfirmed) document.getElementById('delete-payment-'+id).submit(); 
        }); 
    }

    function confirmDeleteAdjustment(id) { 
        window.confirmDialog({ 
            title: 'Hapus Penyesuaian?', 
            text: "Nilai koreksi akan dihapus dan jurnal dibalik.", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#ef4444', 
            confirmButtonText: 'Ya, Hapus!', 
            cancelButtonText: 'Batal' 
        }).then((r) => { 
            if(r.isConfirmed) document.getElementById('delete-adj-'+id).submit(); 
        }); 
    }

    function confirmApprove(id) { 
        window.confirmDialog({ 
            title: 'Setujui Pembayaran?', 
            text: "Dana akan masuk ke pembukuan dan status menjadi Completed.", 
            icon: 'question', 
            showCancelButton: true, 
            confirmButtonColor: '#10b981', 
            confirmButtonText: 'Ya, Setujui!', 
            cancelButtonText: 'Batal' 
        }).then((r) => { 
            if(r.isConfirmed) document.getElementById('form-approve-'+id).submit(); 
        }); 
    }

    function confirmReject(id) { 
        window.confirmDialog({ 
            title: 'Tolak Pembayaran?', 
            text: "Status akan menjadi Failed. Jika ada deposit terkait, akan dibatalkan.", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#ef4444', 
            confirmButtonText: 'Ya, Tolak!', 
            cancelButtonText: 'Batal' 
        }).then((r) => { 
            if(r.isConfirmed) document.getElementById('form-reject-'+id).submit(); 
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
        setTimeout(() => { img.src = ''; }, 300); // Clear src setelah animasi tutup (jika ada)
    }

    // Klik di luar modal untuk tutup
    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeProof();
        }
    });
</script>
@endpush
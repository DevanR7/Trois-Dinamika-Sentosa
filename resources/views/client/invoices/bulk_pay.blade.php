@extends('client.layouts.app')

@section('title', 'Bayar Tagihan Sekaligus')

@section('content')

    <div class="max-w-6xl mx-auto" x-data="bulkPayForm()">
        
        <div class="mb-6">
            <a href="{{ route('client.invoices.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition-colors w-fit mb-2">
                <i class="material-icons text-sm">arrow_back</i> Kembali
            </a>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Pembayaran Massal (Bulk Pay)</h1>
            <p class="text-slate-500 text-sm">Pilih beberapa invoice untuk dibayar sekaligus.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- LEFT COLUMN: Invoice Selector --}}
            {{-- Bagian ini diluar Form agar Alpine bisa baca 'selectedIds' --}}
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="card-header justify-between">
                        <h3 class="card-header-title">Pilih Tagihan</h3>
                        <div class="text-xs text-slate-500">
                            <span class="font-bold text-indigo-600" x-text="selectedIds.length">0</span> dipilih
                        </div>
                    </div>
                    
                    <div class="table-container border-0 shadow-none rounded-none max-h-[600px] overflow-y-auto custom-scrollbar">
                        <table class="table-modern w-full">
                            <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th class="w-10 text-center">
                                        <input type="checkbox" @change="toggleAll" x-model="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </th>
                                    <th>Invoice</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-right">Sisa Tagihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $inv)
                                    <tr class="hover:bg-indigo-50/30 cursor-pointer" @click="toggleRow('{{ $inv->invoice_id }}')">
                                        <td class="text-center" @click.stop>
                                            {{-- Input Checkbox ini HANYA untuk Alpine Model, bukan form submission langsung --}}
                                            <input type="checkbox" value="{{ $inv->invoice_id }}" 
                                                x-model="selectedIds" 
                                                @change="calculateTotal"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td>
                                            <span class="font-bold text-slate-700 dark:text-white">{{ $inv->invoice_number }}</span>
                                            <div class="text-xs text-slate-500">{{ $inv->order_date->format('d/m/Y') }}</div>
                                        </td>
                                        <td>
                                            <span class="{{ $inv->due_date < now() ? 'text-red-500 font-bold' : 'text-slate-600' }}">
                                                {{ $inv->due_date->format('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="text-right font-medium">
                                            Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-8 text-slate-500">Tidak ada tagihan yang belum lunas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Summary & Payment Form --}}
            <div class="lg:col-span-1">
                <div class="card sticky top-24 border-indigo-200 dark:border-indigo-900 shadow-lg">
                    <div class="card-header bg-indigo-50 dark:bg-indigo-900/20">
                        <h3 class="card-header-title text-indigo-700 dark:text-indigo-400">Rincian Pembayaran</h3>
                    </div>

                    {{-- TAB MODE --}}
                    <div class="p-2 flex gap-2 justify-center border-b border-slate-100 dark:border-slate-700">
                        <button type="button" @click="mode = 'manual'" 
                            class="flex-1 py-2 text-xs font-bold rounded-lg transition-colors"
                            :class="mode === 'manual' ? 'bg-indigo-600 text-white shadow' : 'bg-transparent text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                            Upload Bukti
                        </button>
                        
                        {{-- ✅ REVISI: Tampilkan tombol Online hanya jika Gateway Aktif --}}
                        @if($gatewayMethod)
                            <button type="button" @click="mode = 'online'" 
                                class="flex-1 py-2 text-xs font-bold rounded-lg transition-colors"
                                :class="mode === 'online' ? 'bg-indigo-600 text-white shadow' : 'bg-transparent text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                                Online / QRIS
                            </button>
                        @endif
                    </div>

                    <div class="card-body space-y-4">
                        
                        {{-- Calculation Summary (Shared) --}}
                        <div class="space-y-2 text-sm pb-4 border-b border-slate-100 dark:border-slate-700">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Total Tagihan Dipilih</span>
                                <span class="font-bold text-slate-800 dark:text-white" x-text="formatRupiah(totalSelectedAmount)"></span>
                            </div>
                            
                            {{-- Credit Usage Logic --}}
                            @if($availableBalance > 0)
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" x-model="useCredit" @change="calculateTotal" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        <span class="text-emerald-700 font-medium">Gunakan Saldo</span>
                                    </label>
                                    <span class="text-emerald-600" x-text="'- ' + formatRupiah(creditUsed)"></span>
                                </div>
                                <div class="text-[10px] text-slate-400 ml-6">
                                    Tersedia: Rp {{ number_format($availableBalance, 0, ',', '.') }}
                                </div>
                            @endif

                            <div class="flex justify-between pt-2 font-bold text-indigo-700 border-t border-dashed border-slate-200">
                                <span>Total Bayar</span>
                                <span x-text="formatRupiah(finalPaymentAmount)"></span>
                            </div>
                        </div>

                        {{-- ================= FORM MANUAL ================= --}}
                        <form x-show="mode === 'manual'" action="{{ route('client.invoices.bulkPay.storeManual') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            {{-- Inject Selected IDs as Hidden Inputs --}}
                            <template x-for="id in selectedIds" :key="id">
                                <input type="hidden" name="invoice_ids[]" :value="id">
                            </template>
                            
                            {{-- Inject Calculated Amounts --}}
                            <input type="hidden" name="payment_amount" :value="finalPaymentAmount">
                            <input type="hidden" name="use_credit" :value="useCredit ? '1' : '0'">

                            <div class="space-y-4">
                                {{-- 1. Metode Transfer --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Metode Transfer <span class="text-red-500">*</span></label>
                                    <select name="payment_method_id" class="tom-select" x-model="selectedMethodId" required>
                                        <option value="">Pilih Bank...</option>
                                        @foreach($paymentMethods as $pm)
                                            <option value="{{ $pm->payment_method_id }}">{{ $pm->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 2. Bank Tujuan --}}
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Bank Tujuan <span class="text-red-500">*</span></label>
                                    <select name="company_bank_account_id" class="tom-select" x-model="selectedBankId" required>
                                        <option value="">Pilih Rekening...</option>
                                        @foreach($companyBankAccounts as $bank)
                                            <option value="{{ $bank->company_bank_account_id }}">{{ $bank->bank_name }} - {{ $bank->account_number }}</option>
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

                                <div x-show="showProofInput" x-transition>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Bukti Transfer <span class="text-red-500">*</span></label>
                                    <input type="file" name="proof_of_payment" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*" :required="showProofInput">
                                </div>

                                <div x-show="showReferenceInput" x-transition>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">No. Referensi (Opsional)</label>
                                    <input type="text" name="reference_number" class="form-input text-sm" :required="showReferenceInput">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Catatan</label>
                                    <textarea name="notes" rows="2" class="form-input text-sm w-full" placeholder="Keterangan tambahan..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary w-full justify-center py-3" :disabled="selectedIds.length === 0">
                                    <i class="material-icons text-sm mr-2">upload</i> Kirim Bukti Transfer
                                </button>
                            </div>
                        </form>

                        {{-- ================= FORM ONLINE (MIDTRANS) ================= --}}
                        @if($gatewayMethod)
                            <div x-show="mode === 'online'" class="text-center py-4">
                                <i class="material-icons text-4xl text-slate-300 mb-2">qr_code_scanner</i>
                                <p class="text-sm text-slate-500 mb-4">
                                    Bayar <span x-text="selectedIds.length"></span> tagihan sekaligus secara instan.
                                </p>
                                
                                <button type="button" @click="payBatchOnline" class="btn btn-primary w-full justify-center py-3" 
                                    :disabled="selectedIds.length === 0" :class="{'is-loading': loading}">
                                    Bayar Online (Rp <span x-text="formatRupiah(finalPaymentAmount)"></span>)
                                </button>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    @if($gatewayMethod)
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
    @endif
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkPayForm', () => ({
                mode: 'manual', // manual vs online
                selectedIds: [],
                selectAll: false,
                totalSelectedAmount: 0,
                useCredit: false,
                availableCredit: {{ $availableBalance }},
                creditUsed: 0,
                finalPaymentAmount: 0,
                loading: false,

                // Manual Input logic
                selectedMethodId: '',
                selectedBankId: '',
                methodConfigs: {
                    @foreach($paymentMethods as $pm)
                        '{{ $pm->payment_method_id }}': '{{ $pm->client_input_config }}',
                    @endforeach
                },
                bankAccounts: {
                    @foreach($companyBankAccounts as $bank)
                        '{{ $bank->company_bank_account_id }}': {
                            bank_name: '{{ $bank->bank_name }}',
                            account_name: '{{ $bank->account_name }}',
                            account_number: '{{ $bank->account_number }}'
                        },
                    @endforeach
                },
                
                // Data Invoice
                invoices: {
                    @foreach($invoices as $inv)
                        '{{ $inv->invoice_id }}': {{ $inv->remaining_balance }},
                    @endforeach
                },

                get currentConfig() { return this.methodConfigs[this.selectedMethodId] || 'none'; },
                get showProofInput() { return ['proof_only', 'proof_and_reference'].includes(this.currentConfig); },
                get showReferenceInput() { return ['reference_only', 'proof_and_reference'].includes(this.currentConfig); },
                get selectedBank() { return this.bankAccounts[this.selectedBankId] || null; },

                toggleAll() {
                    if (this.selectAll) {
                        this.selectedIds = Object.keys(this.invoices);
                    } else {
                        this.selectedIds = [];
                    }
                    this.calculateTotal();
                },

                toggleRow(id) {
                    if (this.selectedIds.includes(id)) {
                        this.selectedIds = this.selectedIds.filter(i => i !== id);
                    } else {
                        this.selectedIds.push(id);
                    }
                    this.calculateTotal();
                },

                calculateTotal() {
                    // 1. Hitung total tagihan terpilih
                    this.totalSelectedAmount = this.selectedIds.reduce((sum, id) => {
                        return sum + (this.invoices[id] || 0);
                    }, 0);

                    // 2. Hitung penggunaan kredit (Otomatis)
                    if (this.useCredit) {
                        this.creditUsed = Math.min(this.totalSelectedAmount, this.availableCredit);
                    } else {
                        this.creditUsed = 0;
                    }

                    // 3. Hitung sisa yang SEHARUSNYA dibayar
                    this.finalPaymentAmount = Math.max(0, this.totalSelectedAmount - this.creditUsed);
                    
                    // Update UI Checkbox Select All
                    this.selectAll = this.selectedIds.length === Object.keys(this.invoices).length && Object.keys(this.invoices).length > 0;
                },

                formatRupiah(number) {
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number);
                },

                // Logic Midtrans Bulk
                payBatchOnline() {
                    if(this.selectedIds.length === 0) {
                        showToast('Pilih minimal satu tagihan.', 'warning');
                        return;
                    }
                    this.loading = true;

                    fetch('{{ route('client.invoices.bulkPay.storeMidtrans') }}', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                        },
                        body: JSON.stringify({
                            invoice_ids: this.selectedIds,
                            amount: this.totalSelectedAmount, // Total Tagihan Asli
                            use_credit: this.useCredit // Backend akan menghitung ulang sisa bayar
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.loading = false;
                        if(data.snap_token) { 
                            window.snap.pay(data.snap_token, { 
                                onSuccess: () => window.location.href = "{{ route('client.invoices.index') }}", 
                                onPending: () => window.location.href = "{{ route('client.invoices.index') }}", 
                                onError: () => showToast('Pembayaran Gagal', 'error') 
                            }); 
                        }
                        else if(data.status === 'paid_by_credit') { 
                            window.location.href = "{{ route('client.invoices.index') }}"; 
                        }
                        else { 
                            showToast(data.message, 'error'); 
                        }
                    })
                    .catch(err => { 
                        this.loading = false; 
                        console.error(err);
                        showToast('Gagal memproses pembayaran online.', 'error'); 
                    });
                }
            }));
        });
    </script>
    @endpush

@endsection
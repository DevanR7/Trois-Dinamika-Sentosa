@extends('admin.layouts.app')

@section('title', 'Revisi PO #' . $purchaseOrder->po_number)

@section('content')
    {{-- 
        DATA INJECTION: 
        Kita menyuntikkan data item asli agar Alpine bisa menghitung ulang 
        dan membandingkan dengan nilai total awal.
    --}}
    <div x-data="poAdjustmentAuto({
        originalTotal: {{ $purchaseOrder->total_amount }},
        items: {{ json_encode($purchaseOrder->items->map(function($item) {
            return [
                'id' => $item->item_id, 
                'selected' => true, 
                'product_id' => (string) $item->product_id, 
                'price' => (float) $item->price_per_unit,
                'price_visual' => number_format((float) $item->price_per_unit, 0, ',', '.'),
                'qty' => (float) $item->quantity,
                'unit_name' => $item->product->unit->name ?? 'Unit',
                'discounts' => $item->discounts->pluck('percentage')->map(fn($v) => (float)$v)->toArray() ?: [0]
            ];
        })) }},
        config: {
            apply_disc_fee: {{ $purchaseOrder->apply_disc_fee ? 'true' : 'false' }},
            disc_fee_percent: {{ $purchaseOrder->disc_fee_percent ?? 0 }},
            apply_rounding: {{ $purchaseOrder->apply_rounding_discount ? 'true' : 'false' }},
            rounding_amount: {{ $purchaseOrder->rounding_discount_amount ?? 0 }},
            use_custom_dpp: {{ $purchaseOrder->use_custom_dpp_factor ? 'true' : 'false' }},
            custom_dpp_factor: '{{ $purchaseOrder->custom_dpp_factor ?? 1 }}',
            tax_id: '{{ $purchaseOrder->tax_id }}',
            shipping_amount: {{ $purchaseOrder->shipping_amount ?? 0 }}
        }
    })" class="flex flex-col gap-6 pb-20">

        {{-- Top Bar --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="page-title">Revisi Otomatis PO</h2>
                <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                    <span class="font-bold text-slate-700 dark:text-white">PO #{{ $purchaseOrder->po_number }}</span>
                    <span>&bull;</span>
                    <span>Total Awal: <span class="font-mono">{{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</span></span>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.purchase-order-adjustments.create') }}" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" form="adjustment-form" class="btn btn-primary" :disabled="diffValue === 0">
                    <i class="material-icons text-lg">save</i>
                    Simpan Revisi
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-lg text-sm animate-enter">
                <p class="font-bold mb-1">Terdapat kesalahan:</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="adjustment-form" action="{{ route('admin.purchase-order-adjustments.store.auto', $purchaseOrder->po_id) }}" method="POST">
            @csrf

            {{-- 1. TABEL ITEM (Mode Edit) --}}
            <div class="card mb-6 overflow-hidden">
                <div class="card-header bg-slate-50/50 flex justify-between items-center">
                    <h3 class="card-header-title">Detail Item (Edit untuk Revisi)</h3>
                    
                    {{-- Bulk Discount Tool --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Set Diskon:</span>
                        <input type="text" x-model="bulkDiscValue" class="form-input h-8 text-sm w-24" placeholder="Cth: 10+5">
                        <button type="button" @click="applyBulkDiscount()" class="btn btn-sm btn-secondary h-8">
                            Terapkan
                        </button>
                    </div>
                </div>

                <div class="table-container border-0 shadow-none rounded-none overflow-x-auto">
                    <table class="table-modern w-full min-w-[1000px]">
                        <thead class="bg-slate-100 dark:bg-slate-800">
                            <tr>
                                <th class="min-w-[300px]">Produk</th>
                                <th class="w-[150px] text-right">Harga (Rp)</th>
                                <th class="w-[160px] text-center">Qty</th>
                                <th class="w-[180px] text-center">Diskon (%)</th>
                                <th class="w-[180px] text-right">Subtotal</th>
                                <th class="w-[50px]"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr>
                                    {{-- Product Selection --}}
                                    <td class="align-top p-2">
                                        <select :name="`products[${index}][product_id]`" 
                                                x-model="item.product_id"
                                                x-init="initTomSelect($el, index)"
                                                class="tom-select w-full" required>
                                            <option value="">Pilih Produk...</option>
                                            @foreach($products as $prod)
                                                <option value="{{ $prod->product_id }}" 
                                                        data-price="{{ $prod->purchase_price ?? 0 }}" 
                                                        data-unit="{{ $prod->unit->name ?? 'Unit' }}">
                                                    {{ $prod->product_name }} ({{ $prod->product_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- Price --}}
                                    <td class="align-top p-2">
                                        <input type="text" 
                                               x-model="item.price_visual"
                                               @input="formatPriceInput(index, $event.target.value)"
                                               class="form-input text-right w-full text-sm" placeholder="0">
                                        <input type="hidden" :name="`products[${index}][price_per_unit]`" :value="item.price">
                                    </td>

                                    {{-- Qty --}}
                                    <td class="align-top p-2">
                                        <div class="flex">
                                            <input type="number" :name="`products[${index}][quantity]`" 
                                                   x-model.number="item.qty" class="form-input text-center w-full rounded-r-none border-r-0" step="0.01" required>
                                            <span class="inline-flex items-center px-2 text-xs bg-slate-100 border border-l-0 border-slate-300 rounded-r text-slate-500" x-text="item.unit_name || 'Unit'"></span>
                                        </div>
                                    </td>

                                    {{-- Discount Multi-Level --}}
                                    <td class="align-top p-2">
                                        <div class="flex flex-col gap-1">
                                            <template x-for="(d, dIndex) in item.discounts" :key="dIndex">
                                                <div class="flex items-center gap-1">
                                                    <input type="number" x-model="item.discounts[dIndex]" class="form-input text-center text-xs h-7 w-full" placeholder="%" min="0" max="100" step="0.01">
                                                    <button type="button" @click="removeDiscountLevel(index, dIndex)" x-show="item.discounts.length > 1" class="text-rose-500 hover:text-rose-700">
                                                        <i class="material-icons text-sm">cancel</i>
                                                    </button>
                                                </div>
                                            </template>
                                            <button type="button" @click="addDiscountLevel(index)" class="text-[10px] text-indigo-600 hover:underline text-center w-full mt-1">+ Level</button>
                                            
                                            {{-- Hidden Inputs for Array Submission --}}
                                            <template x-for="(d, dIndex) in item.discounts" :key="`h-${dIndex}`">
                                                <input type="hidden" :name="`products[${index}][discounts][]`" :value="d">
                                            </template>
                                        </div>
                                    </td>

                                    {{-- Subtotal --}}
                                    <td class="align-top p-2 text-right font-medium pt-3">
                                        <span x-text="formatRupiah(calculateRowTotal(item))"></span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="align-top p-2 text-center pt-3">
                                        <button type="button" @click="removeItem(index)" class="text-rose-500 hover:bg-rose-50 p-1 rounded transition-colors" title="Hapus">
                                            <i class="material-icons text-lg">delete_outline</i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    
                    <div class="p-3 bg-slate-50 border-t border-slate-200">
                        <button type="button" @click="addItem()" class="btn btn-sm btn-secondary border-dashed border-2 border-slate-300 text-slate-500 hover:text-indigo-600 hover:border-indigo-400">
                            <i class="material-icons text-base mr-1">add</i> Tambah Produk
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. BOTTOM LAYOUT --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- Left: Notes & Overpayment Config --}}
                <div class="space-y-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-header-title">Alasan Penyesuaian & Catatan</h3>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" rows="4" class="form-input w-full" placeholder="Jelaskan kenapa PO ini direvisi..." required></textarea>
                            <p class="text-xs text-slate-400 mt-2">* Catatan ini akan muncul di Nota Debit/Kredit.</p>
                        </div>
                    </div>

                     {{-- Overpayment Logic --}}
                     <div class="bg-indigo-50 dark:bg-slate-800 p-5 rounded-xl border border-indigo-100 dark:border-slate-700">
                        <label class="form-label mb-2 flex items-center gap-2 text-indigo-700 dark:text-indigo-300">
                            <i class="material-icons">account_balance_wallet</i>
                            Konfigurasi Kelebihan Bayar
                        </label>
                        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                            Jika revisi menyebabkan total baru menjadi lebih kecil dari jumlah yang sudah Anda bayarkan ke Supplier:
                        </p>
                        <div class="flex flex-col gap-3">
                            <label class="inline-flex items-center cursor-pointer p-2 rounded-lg hover:bg-white/50 border border-transparent hover:border-indigo-200 transition-all">
                                <input type="radio" name="overpayment_action" value="deposit" class="form-radio text-indigo-600 w-4 h-4" checked>
                                <span class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-200">Simpan sbg Deposit Supplier (Potongan Pembelian Berikutnya)</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer p-2 rounded-lg hover:bg-white/50 border border-transparent hover:border-indigo-200 transition-all">
                                <input type="radio" name="overpayment_action" value="refund" class="form-radio text-indigo-600 w-4 h-4">
                                <span class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-200">Biarkan (Akan diproses Refund Tunai manual)</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Right: Kalkulasi Akhir (Sama dengan Edit PO) --}}
                <div class="card h-fit border-l-4" :class="adjustmentStatusClass">
                    <div class="card-header bg-white dark:bg-slate-800">
                        <h3 class="card-header-title">Kalkulasi Akhir</h3>
                    </div>
                    <div class="card-body space-y-4">
                        
                        {{-- Subtotal --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-600">Subtotal Item</span>
                            <span class="font-bold" x-text="formatRupiah(totals.subtotal)"></span>
                        </div>

                        {{-- Diskon Akhir --}}
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="apply_disc_fee" value="1" x-model="applyDisc" class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-600">Diskon Akhir (%)</span>
                            </div>
                            <div x-show="applyDisc" class="w-24">
                                <input type="number" name="disc_fee_percent" x-model.number="discPercent" class="form-input text-right h-8 text-sm" placeholder="0" step="0.01">
                            </div>
                        </div>
                        <div x-show="applyDisc && totals.discAmount > 0" class="flex justify-between text-sm text-emerald-600">
                            <span>Potongan</span>
                            <span>- <span x-text="formatRupiah(totals.discAmount)"></span></span>
                        </div>

                        {{-- Pembulatan --}}
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="apply_rounding_discount" value="1" x-model="applyRounding" class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-600">Diskon Pembulatan</span>
                            </div>
                            <div x-show="applyRounding" class="w-32">
                                <input type="number" name="rounding_discount_amount" x-model.number="roundingAmount" class="form-input text-right h-8 text-sm" placeholder="0" step="100">
                            </div>
                        </div>

                        <hr class="border-slate-200 dark:border-slate-600">

                        {{-- DPP Custom (Persis Edit PO) --}}
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-lg border border-indigo-100 dark:border-indigo-800">
                            <div class="flex items-center justify-between mb-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="use_custom_dpp_factor" value="1" x-model="useCustomDPP" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm font-bold text-indigo-700 dark:text-indigo-300">Gunakan Faktor DPP</span>
                                </label>
                            </div>
                            
                            <div x-show="useCustomDPP" x-transition class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-500">Nilai Faktor (Pecahan/Desimal)</span>
                                    <input type="text" x-model="dppInput" class="form-input h-8 text-right text-xs w-24" placeholder="11/12">
                                </div>
                                {{-- Hidden Input untuk kirim nilai kalkulasi ke server jika perlu --}}
                                <input type="hidden" name="custom_dpp_factor" :value="dppFactorValue">

                                <div class="flex justify-between text-xs text-indigo-600 font-medium">
                                    <span>Nilai DPP</span>
                                    <span x-text="formatRupiah(totals.dpp)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Pajak --}}
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-slate-600">PPN / Pajak</span>
                            <div class="w-48">
                                <select name="tax_id" x-model="taxId" class="tom-select w-full" x-init="initTaxSelect($el)">
                                    <option value="">Tanpa Pajak</option>
                                    @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div x-show="totals.ppn > 0" class="flex justify-between text-sm text-slate-600">
                            <span>Nilai Pajak</span>
                            <span>+ <span x-text="formatRupiah(totals.ppn)"></span></span>
                        </div>

                        {{-- Ongkir --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600">Biaya Kirim / Lainnya</span>
                            <input type="number" name="shipping_amount" x-model.number="shipping" class="form-input text-right h-9 w-40" placeholder="0" step="1000">
                        </div>

                        <hr class="border-slate-200 dark:border-slate-600 border-dashed">

                        {{-- RESULT COMPARISON --}}
                        <div class="space-y-3">
                            <div class="flex justify-between text-slate-500 text-sm">
                                <span>Total Lama (Original)</span>
                                <span class="line-through decoration-slate-400" x-text="formatRupiah(originalTotal)"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-900 rounded-lg">
                                <span class="font-bold text-slate-800 dark:text-white">Total Baru (Revisi)</span>
                                <span class="text-xl font-extrabold text-slate-800 dark:text-white" x-text="formatRupiah(totals.grandTotal)"></span>
                                <input type="hidden" name="total_amount" :value="totals.grandTotal">
                            </div>
                        </div>

                        {{-- ADJUSTMENT PREVIEW BOX --}}
                        <div class="p-5 rounded-xl text-center border mt-6 transition-all duration-300 shadow-sm"
                             :class="adjustmentColorClass">
                            <span class="text-[10px] uppercase font-bold tracking-widest block mb-2 opacity-70">Status Penyesuaian</span>
                            
                            <template x-if="diffValue > 0">
                                <div>
                                    <h4 class="text-xl font-black">CREDIT NOTE</h4>
                                    <p class="text-xs font-medium mt-1 mb-2 opacity-90">Total berkurang (Potongan Hutang)</p>
                                    <p class="text-3xl font-extrabold" x-text="formatRupiah(diffValue)"></p>
                                </div>
                            </template>

                            <template x-if="diffValue < 0">
                                <div>
                                    <h4 class="text-xl font-black">DEBIT NOTE</h4>
                                    <p class="text-xs font-medium mt-1 mb-2 opacity-90">Total bertambah (Tagihan Tambahan)</p>
                                    <p class="text-3xl font-extrabold" x-text="formatRupiah(Math.abs(diffValue))"></p>
                                </div>
                            </template>

                            <template x-if="diffValue === 0">
                                <div class="text-slate-400 py-2">
                                    <i class="material-icons text-5xl block mb-2 opacity-50">balance</i>
                                    <span class="text-sm font-medium">Tidak ada perubahan nilai.</span>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>
            </div>

        </form>
    </div>

    @push('scripts')
    <script>
        const taxRates = @json(\App\Models\Tax::where('is_active', true)->pluck('rate', 'id'));

        document.addEventListener('alpine:init', () => {
            Alpine.data('poAdjustmentAuto', (initData) => ({
                items: initData.items,
                originalTotal: parseFloat(initData.originalTotal),
                
                // Config State
                applyDisc: initData.config.apply_disc_fee,
                discPercent: initData.config.disc_fee_percent,
                applyRounding: initData.config.apply_rounding,
                roundingAmount: initData.config.rounding_amount,
                shipping: initData.config.shipping_amount,
                taxId: initData.config.tax_id || '',
                
                // DPP Settings
                useCustomDPP: initData.config.use_custom_dpp,
                dppInput: initData.config.custom_dpp_factor,

                bulkDiscValue: '',

                init() {
                    // Watchers or extra init logic if needed
                },

                // --- Helper Logic ---
                addItem() {
                    this.items.push({ 
                        id: Date.now(), 
                        product_id: '', 
                        price: 0, price_visual: '', 
                        qty: 1, unit_name: '', 
                        discounts: [0] 
                    });
                },
                removeItem(index) {
                    if (this.items.length > 1) this.items.splice(index, 1);
                },
                
                // TomSelect Init
                initTomSelect(el, index) {
                    if (el.tomselect) el.tomselect.destroy();
                    const ts = new TomSelect(el, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => {
                            this.items[index].product_id = value;
                            const option = el.querySelector(`option[value="${value}"]`);
                            if (option) {
                                // Saat ganti produk, ambil harga master baru
                                const price = parseFloat(option.dataset.price) || 0;
                                this.items[index].price = price;
                                this.items[index].price_visual = this.formatRupiah(price, false);
                                this.items[index].unit_name = option.dataset.unit || 'Unit';
                            }
                        }
                    });
                    // Set initial value silently
                    if (this.items[index].product_id) ts.setValue(this.items[index].product_id, true);
                },

                // Tax Select Init
                initTaxSelect(el) {
                    if (el.tomselect) el.tomselect.destroy();
                    const ts = new TomSelect(el, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => { this.taxId = value; }
                    });
                    if (this.taxId) ts.setValue(this.taxId, true);
                },

                // Pricing Logic
                formatPriceInput(index, value) {
                    const raw = value.replace(/\D/g, '');
                    const floatVal = parseFloat(raw) || 0;
                    this.items[index].price = floatVal;
                    this.items[index].price_visual = new Intl.NumberFormat('id-ID').format(floatVal);
                },

                calculateRowTotal(item) {
                    let price = parseFloat(item.price) || 0;
                    const qty = parseFloat(item.qty) || 0;
                    
                    // Apply discounts
                    if (item.discounts && item.discounts.length) {
                        item.discounts.forEach(d => {
                            let val = parseFloat(d);
                            if(val > 0) price = price * (1 - (val/100));
                        });
                    }
                    return price * qty;
                },

                addDiscountLevel(index) { this.items[index].discounts.push(0); },
                removeDiscountLevel(index, dIndex) { this.items[index].discounts.splice(dIndex, 1); },

                applyBulkDiscount() {
                    if(!this.bulkDiscValue) return;
                    const parts = this.bulkDiscValue.toString().split('+').map(d => parseFloat(d) || 0);
                    this.items.forEach(item => item.discounts = [...parts]);
                    showToast('Diskon massal diterapkan', 'info');
                },

                // --- MAIN CALCULATION (Same as Edit PO) ---
                get totals() {
                    const subtotal = this.items.reduce((sum, item) => sum + this.calculateRowTotal(item), 0);
                    let current = subtotal;

                    // Global Discount
                    if (this.applyDisc) {
                        current -= subtotal * ((parseFloat(this.discPercent) || 0) / 100);
                    }
                    
                    let discAmount = subtotal - current; // Helper for display

                    // Rounding
                    if (this.applyRounding) {
                        current -= (parseFloat(this.roundingAmount) || 0);
                    }

                    // DPP Logic
                    let dpp = current;
                    let factor = 1; 
                    if(this.useCustomDPP) {
                        try { 
                            const input = this.dppInput.toString().replace(/\s/g, '').replace(',', '.');
                            if (input.includes('/')) {
                                const parts = input.split('/');
                                if (parts.length === 2 && parseFloat(parts[1]) !== 0) {
                                    factor = parseFloat(parts[0]) / parseFloat(parts[1]);
                                }
                            } else {
                                factor = parseFloat(input) || 1;
                            }
                        } catch(e){ factor=1; }
                        dpp = current * factor;
                    }

                    // Tax
                    let ppn = 0;
                    if (this.taxId && taxRates[this.taxId]) {
                        ppn = dpp * (parseFloat(taxRates[this.taxId]) / 100);
                    }

                    const grandTotal = current + ppn + (parseFloat(this.shipping) || 0);

                    return {
                        subtotal,
                        discAmount,
                        dpp,
                        ppn,
                        grandTotal: Math.max(0, Math.round(grandTotal))
                    };
                },
                
                get dppFactorCalc() {
                     // Digunakan untuk hidden input value jika diperlukan
                     let val = 1;
                     if (!this.useCustomDPP) return 1;
                     try { 
                        const input = this.dppInput.toString().replace(/\s/g, '').replace(',', '.');
                        if (input.includes('/')) {
                            const parts = input.split('/');
                            if (parts.length === 2 && parseFloat(parts[1]) !== 0) val = parseFloat(parts[0]) / parseFloat(parts[1]);
                        } else {
                            val = parseFloat(input) || 1;
                        }
                     } catch(e) {}
                     return val;
                },

                get dppFactorValue() {
                    return this.dppFactorCalc.toFixed(8);
                },

                // --- ADJUSTMENT SPECIFIC ---
                get diffValue() {
                    // Original - New
                    return this.originalTotal - this.totals.grandTotal;
                },

                get adjustmentStatusClass() {
                    if (this.diffValue > 0) return 'border-emerald-500'; // Credit Note
                    if (this.diffValue < 0) return 'border-rose-500';    // Debit Note
                    return 'border-slate-300';
                },

                get adjustmentColorClass() {
                    if (this.diffValue > 0) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (this.diffValue < 0) return 'bg-rose-50 text-rose-700 border-rose-200';
                    return 'bg-slate-50 text-slate-500 border-slate-200';
                },

                formatRupiah(value, withSymbol = true) {
                    return new Intl.NumberFormat('id-ID', {
                        style: withSymbol ? 'currency' : 'decimal',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(value || 0);
                }
            }));
        });
    </script>
    @endpush
@endsection
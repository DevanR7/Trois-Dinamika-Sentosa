@extends('admin.layouts.app')

@section('title', 'Edit Purchase Order #' . $purchaseOrder->po_number)

@section('content')

    {{-- 
       DATA INJECTION KE ALPINE JS 
       Kita memformat data dari PHP ke JSON agar Alpine bisa merender baris item yang sudah ada.
    --}}
    <div x-data="purchaseOrderEdit({
        items: {{ json_encode($purchaseOrder->items->map(function($item) {
            return [
                'id' => $item->item_id, 
                'selected' => false,
                'product_id' => (string) $item->product_id, 
                'price' => (float) $item->price_per_unit,
                'price_visual' => number_format((float) $item->price_per_unit, 0, ',', '.'),
                'qty' => (float) $item->quantity,
                'unit_name' => $item->product->unit->name ?? 'Unit',
                // Ambil diskon jika ada, jika tidak default [0]
                'discounts' => $item->discounts->count() > 0 
                                ? $item->discounts->pluck('percentage')->map(fn($v) => (float)$v)->toArray() 
                                : [0],
                'update_master_price' => false
            ];
        })) }},
        config: {
            apply_disc_fee: {{ $purchaseOrder->apply_disc_fee ? 'true' : 'false' }},
            disc_fee_percent: {{ $purchaseOrder->disc_fee_percent ?? 0 }},
            apply_rounding: {{ $purchaseOrder->apply_rounding_discount ? 'true' : 'false' }},
            rounding_amount: {{ $purchaseOrder->rounding_discount_amount ?? 0 }},
            use_custom_dpp: {{ $purchaseOrder->use_custom_dpp_factor ? 'true' : 'false' }},
            custom_dpp_factor: '{{ $purchaseOrder->custom_dpp_factor ?? "11/12" }}',
            tax_id: '{{ $purchaseOrder->tax_id }}',
            shipping_amount: {{ $purchaseOrder->shipping_amount ?? 0 }}
        }
    })" class="flex flex-col gap-6 pb-20">

        {{-- Top Bar --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="page-title">Edit Pesanan Pembelian</h2>
                <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                    <a href="{{ route('admin.purchase-orders.index') }}" class="hover:text-indigo-600 transition-colors">Daftar PO</a>
                    <span>/</span>
                    <span class="font-mono font-bold">{{ $purchaseOrder->po_number }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.purchase-orders.show', $purchaseOrder->po_id) }}" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" form="po-form" class="btn btn-primary">
                    <i class="material-icons text-lg">save</i>
                    Simpan Perubahan
                </button>
            </div>
        </div>

        {{-- Error Summary --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-lg text-sm">
                <p class="font-bold mb-1">Gagal menyimpan data:</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="po-form" action="{{ route('admin.purchase-orders.update', $purchaseOrder->po_id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- 1. INFORMASI UTAMA --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-header-title">Informasi Pesanan & Supplier</h3>
                </div>
                <div class="card-body grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Supplier --}}
                    <div>
                        <label class="form-label">Supplier <span class="text-red-500">*</span></label>
                        <select name="supplier_id" class="tom-select w-full" required>
                            <option value="">Pilih Supplier...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}" {{ old('supplier_id', $purchaseOrder->supplier_id) == $supplier->supplier_id ? 'selected' : '' }}>
                                    {{ $supplier->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal Order --}}
                    <div>
                        <label class="form-label">Tanggal Order <span class="text-red-500">*</span></label>
                        <input type="date" name="order_date" value="{{ old('order_date', $purchaseOrder->order_date->format('Y-m-d')) }}" class="form-input" required>
                    </div>

                    {{-- Jatuh Tempo --}}
                    <div>
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="due_date" value="{{ old('due_date', optional($purchaseOrder->due_date)->format('Y-m-d')) }}" class="form-input">
                    </div>

                    {{-- Peminta --}}
                    <div>
                        <label class="form-label">Diminta Oleh</label>
                        <select name="requester_user_id" class="tom-select w-full">
                            <option value="">Pilih Staff...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->user_id }}" {{ old('requester_user_id', $purchaseOrder->requester_user_id) == $user->user_id ? 'selected' : '' }}>
                                    {{ $user->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- 2. TABEL ITEM BARANG --}}
            <div class="card mb-6 overflow-hidden">
                <div class="card-header flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <h3 class="card-header-title">Item Barang</h3>
                        <span class="text-xs text-slate-400">(Input harga sebelum pajak)</span>
                    </div>

                    {{-- Bulk Discount Tool --}}
                    <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg p-1.5 shadow-sm">
                        <span class="text-xs font-bold text-slate-500 uppercase ml-2">Diskon Massal:</span>
                        <input type="text" x-model="bulkDiscValue" class="form-input h-8 text-sm w-32" placeholder="Cth: 50+9.91">
                        
                        <button type="button" @click="applyBulkDiscount('selected')" 
                                class="btn btn-sm btn-secondary h-8 px-3 border-r rounded-r-none whitespace-nowrap">
                            Ke Terpilih
                        </button>
                        <button type="button" @click="applyBulkDiscount('all')" 
                                class="btn btn-sm btn-primary h-8 px-3 rounded-l-none whitespace-nowrap">
                            Ke Semua
                        </button>
                    </div>
                </div>
                
                <div class="table-container border-0 shadow-none rounded-none overflow-x-auto">
                    <table class="table-modern w-full min-w-[1300px]"> 
                        <thead class="bg-slate-100 dark:bg-slate-800">
                            <tr>
                                <th class="w-[40px] text-center px-1">
                                    <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                </th>
                                <th class="min-w-[350px]">Produk</th>
                                <th class="w-[180px] text-right">Harga Beli (Rp)</th>
                                <th class="w-[200px] text-center">Qty</th>
                                <th class="w-[220px] text-center">
                                    Diskon Bertingkat (%)
                                    <span class="text-[10px] block text-slate-400 font-normal">cth: 50+9.91</span>
                                </th>
                                <th class="w-[180px] text-right">Total (Rp)</th>
                                <th class="w-[50px] text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr>
                                    {{-- Checkbox --}}
                                    <td class="align-top p-2 text-center pt-4">
                                        <input type="checkbox" x-model="item.selected" class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    </td>
                                    
                                    {{-- Produk --}}
                                    <td class="align-top p-2">
                                        <select :name="`products[${index}][product_id]`" 
                                                x-model="item.product_id"
                                                x-init="initTomSelect($el, index)"
                                                class="tom-select w-full" required>
                                            <option value="">Cari Produk...</option>
                                            @foreach($products as $prod)
                                                <option value="{{ $prod->product_id }}" 
                                                        data-price="{{ $prod->purchase_price ?? 0 }}" 
                                                        data-unit="{{ $prod->unit->name ?? 'Pcs' }}">
                                                    {{ $prod->product_name }} ({{ $prod->product_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        
                                        <div class="mt-1 flex gap-2">
                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input type="checkbox" :name="`products[${index}][update_master_price]`" x-model="item.update_master_price" value="1" class="rounded text-xs text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-[10px] text-slate-500">Update harga master</span>
                                            </label>
                                        </div>
                                    </td>

                                    {{-- Harga Beli --}}
                                    <td class="align-top p-2">
                                        <input type="text" 
                                               x-model="item.price_visual"
                                               @input="formatPriceInput(index, $event.target.value)"
                                               class="form-input text-right text-sm w-full" 
                                               placeholder="0">
                                        {{-- Hidden input untuk kirim nilai float asli --}}
                                        <input type="hidden" :name="`products[${index}][price_per_unit]`" :value="item.price">
                                    </td>

                                    {{-- Qty --}}
                                    <td class="align-top p-2">
                                        <div class="flex items-center">
                                            <input type="number" :name="`products[${index}][quantity]`" x-model.number="item.qty" 
                                                   class="form-input text-center text-sm w-full rounded-r-none border-r-0 min-w-[80px]" 
                                                   min="0.01" step="0.01" placeholder="1" required>
                                            <span class="inline-flex items-center px-3 text-xs text-slate-500 bg-slate-100 border border-slate-300 rounded-r-lg dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600 h-[38px] min-w-[50px] justify-center truncate" 
                                                  x-text="item.unit_name || 'Unit'">
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Diskon --}}
                                    <td class="align-top p-2">
                                        <div class="flex flex-col gap-1 items-center">
                                            <template x-for="(d, dIndex) in item.discounts" :key="dIndex">
                                                <div class="flex items-center gap-1 w-full justify-center">
                                                    <span class="text-[10px] text-slate-400 w-3 text-right" x-text="dIndex + 1 + '.'"></span>
                                                    <input type="number" x-model="item.discounts[dIndex]" 
                                                           @input="updateDiscString(index)"
                                                           class="form-input text-center text-xs h-7 w-20" 
                                                           placeholder="%" min="0" max="100" step="0.01">
                                                    
                                                    <button type="button" @click="removeDiscountLevel(index, dIndex)" 
                                                            x-show="item.discounts.length > 1"
                                                            class="text-rose-400 hover:text-rose-600 w-4">
                                                        <i class="material-icons text-sm">remove_circle</i>
                                                    </button>
                                                    <div x-show="item.discounts.length <= 1" class="w-4"></div>
                                                </div>
                                            </template>
                                            
                                            <button type="button" @click="addDiscountLevel(index)" 
                                                    class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mt-1 justify-center w-full">
                                                <i class="material-icons text-[12px]">add</i> Level
                                            </button>
                                            
                                            {{-- Input Hidden Array untuk Backend --}}
                                            <template x-for="(d, dIndex) in item.discounts" :key="`hidden-${dIndex}`">
                                                <input type="hidden" :name="`products[${index}][discounts][]`" :value="d">
                                            </template>
                                        </div>
                                    </td>

                                    {{-- Subtotal --}}
                                    <td class="align-top p-2 text-right font-bold text-slate-700 dark:text-white pt-3">
                                        <span x-text="formatRupiah(calculateRowTotal(item))"></span>
                                    </td>

                                    {{-- Hapus Baris --}}
                                    <td class="align-top p-2 text-center pt-3">
                                        <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 p-1 bg-rose-50 rounded" title="Hapus Baris">
                                            <i class="material-icons text-lg">close</i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    
                    <div class="p-4 bg-slate-50 border-t border-slate-200">
                        <button type="button" @click="addItem()" class="btn btn-sm btn-secondary inline-flex items-center gap-2 border-dashed border-2 border-slate-300 hover:border-indigo-400 hover:bg-indigo-50 text-slate-500 hover:text-indigo-600 transition-colors w-auto">
                            <i class="material-icons text-lg">add_circle_outline</i> Tambah Baris Barang
                        </button>
                    </div>
                </div>
            </div>

            {{-- 3. BOTTOM SECTION --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- KIRI: Catatan --}}
                <div class="card h-fit">
                    <div class="card-header">
                        <h3 class="card-header-title">Catatan Tambahan</h3>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" rows="4" class="form-input w-full" placeholder="Tulis catatan...">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                    </div>
                </div>

                {{-- KANAN: Ringkasan Biaya --}}
                <div class="card bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <div class="card-header bg-white dark:bg-slate-800">
                        <h3 class="card-header-title">Ringkasan Biaya</h3>
                    </div>
                    <div class="card-body space-y-3">
                        
                        {{-- Subtotal --}}
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-600">Subtotal</span>
                            <span class="font-bold text-base" x-text="formatRupiah(totals.subtotal)"></span>
                            <input type="hidden" name="subtotal" :value="totals.subtotal">
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

                        {{-- Diskon Pembulatan --}}
                         <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="apply_rounding_discount" value="1" x-model="applyRounding" class="rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-600">Diskon Pembulatan (Nominal)</span>
                            </div>
                            <div x-show="applyRounding" class="w-32">
                                <input type="number" name="rounding_discount_amount" x-model.number="roundingAmount" class="form-input text-right h-8 text-sm" placeholder="0" step="100">
                            </div>
                        </div>

                        <hr class="border-slate-200 dark:border-slate-600">

                        {{-- DPP Custom --}}
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
                                
                                <input type="hidden" name="custom_dpp_factor" :value="dppFactorValue">
                                
                                @error('custom_dpp_factor')
                                    <p class="text-red-500 text-xs text-right">{{ $message }}</p>
                                @enderror

                                <div class="flex justify-between text-xs text-indigo-600 font-medium">
                                    <span>Nilai DPP</span>
                                    <span x-text="formatRupiah(totals.dpp)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Pajak (Tom Select) --}}
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-slate-600">PPN / Pajak</span>
                            <div class="w-48">
                                <select name="tax_id" x-model="taxId" x-init="initTaxSelect($el)" class="tom-select w-full">
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

                        {{-- Grand Total --}}
                        <div class="flex justify-between items-center py-2">
                            <span class="text-lg font-extrabold text-slate-800 dark:text-white">GRAND TOTAL</span>
                            <span class="text-2xl font-extrabold text-indigo-600" x-text="formatRupiah(totals.grandTotal)"></span>
                            <input type="hidden" name="total_amount" :value="totals.grandTotal">
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
            Alpine.data('purchaseOrderEdit', (initData) => ({
                selectAll: false,
                items: initData.items, // Load data dari controller
                
                // Load config dari controller
                applyDisc: initData.config.apply_disc_fee,
                discPercent: initData.config.disc_fee_percent,
                applyRounding: initData.config.apply_rounding,
                roundingAmount: initData.config.rounding_amount,
                shipping: initData.config.shipping_amount,
                taxId: initData.config.tax_id || '',
                
                useCustomDPP: initData.config.use_custom_dpp,
                dppInput: initData.config.custom_dpp_factor, 
                
                bulkDiscValue: '',

                init() {
                    // Watch deep agar perubahan di item memicu re-render
                    this.$watch('items', () => {}, { deep: true });
                },

                toggleSelectAll() { this.items.forEach(item => item.selected = this.selectAll); },

                initTomSelect(el, index) {
                    if (el.tomselect) el.tomselect.destroy();
                    const ts = new TomSelect(el, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => {
                            this.items[index].product_id = value;
                            const option = el.querySelector(`option[value="${value}"]`);
                            if (option) {
                                const price = parseFloat(option.dataset.price) || 0;
                                const unit = option.dataset.unit || 'Unit';
                                
                                // Hanya set nilai jika belum ada (atau jika user eksplisit ganti produk)
                                // Tapi di mode edit, kita load nilai dari DB. 
                                // Jika user ganti produk, baru kita override.
                                // Logic: Value berubah -> TomSelect trigger onChange.
                                
                                // Sederhananya: Kita update state. Jika user ingin harga lama, mereka bisa ketik ulang.
                                this.items[index].price = price;
                                this.items[index].price_visual = this.formatRupiah(price, false);
                                this.items[index].unit_name = unit;
                            }
                        }
                    });
                    
                    // Set Initial Value agar tidak trigger change berlebihan saat init
                    if(this.items[index].product_id) {
                         ts.setValue(this.items[index].product_id, true);
                    }
                },

                initTaxSelect(el) {
                    if (el.tomselect) el.tomselect.destroy();
                    const ts = new TomSelect(el, {
                        ...window.defaultTomSelectConfig,
                        onChange: (value) => { this.taxId = value; }
                    });
                    if(this.taxId) ts.setValue(this.taxId, true);
                },

                addItem() {
                    this.items.push({ id: Date.now(), selected: false, product_id: '', price: 0, price_visual: '', qty: 1, unit_name: '', discounts: [0], update_master_price: false });
                },

                removeItem(index) {
                    if (this.items.length > 1) { this.items.splice(index, 1); }
                    else {
                         // Reset baris terakhir
                         this.items[0].product_id = ''; 
                         this.items[0].price = 0; 
                         this.items[0].price_visual = ''; 
                         this.items[0].qty = 1; 
                         this.items[0].discounts = [0];
                         
                         const selectEl = document.querySelector(`select[name="products[0][product_id]"]`);
                         if(selectEl && selectEl.tomselect) selectEl.tomselect.clear();
                    }
                },

                addDiscountLevel(index) { this.items[index].discounts.push(0); },
                removeDiscountLevel(index, dIndex) { this.items[index].discounts.splice(dIndex, 1); },
                updateDiscString(index) {}, 

                applyBulkDiscount(target) {
                    if(!this.bulkDiscValue) return;
                    const parts = this.bulkDiscValue.toString().split('+').map(d => parseFloat(d) || 0);
                    let appliedCount = 0;
                    this.items.forEach(item => {
                        if (target === 'all' || (target === 'selected' && item.selected)) {
                            item.discounts = [...parts]; 
                            appliedCount++;
                        }
                    });
                    if (appliedCount > 0) showToast(`Diskon massal diterapkan.`, 'success');
                    else showToast('Tidak ada item yang dipilih.', 'warning');
                },

                formatPriceInput(index, value) {
                    const rawValue = value.replace(/\D/g, '');
                    const floatValue = parseFloat(rawValue) || 0;
                    this.items[index].price = floatValue;
                    this.items[index].price_visual = new Intl.NumberFormat('id-ID').format(floatValue);
                },

                get dppFactorCalc() {
                    let val = 1;
                    if (!this.useCustomDPP) return 1;

                    try {
                        const input = this.dppInput.toString().replace(/\s/g, '').replace(',', '.');
                        if (input.includes('/')) {
                            const parts = input.split('/');
                            if (parts.length === 2) {
                                const num = parseFloat(parts[0]);
                                const den = parseFloat(parts[1]);
                                if (!isNaN(num) && !isNaN(den) && den !== 0) val = num / den;
                            }
                        } else {
                            const floatVal = parseFloat(input);
                            if (!isNaN(floatVal)) val = floatVal;
                        }
                    } catch (e) { val = 1; }
                    return val;
                },

                get dppFactorValue() {
                    return this.dppFactorCalc.toFixed(8);
                },

                calculateRowTotal(item) {
                    const price = parseFloat(item.price) || 0;
                    const qty = parseFloat(item.qty) || 0;
                    let finalPrice = price;
                    
                    if (item.discounts && item.discounts.length > 0) {
                        item.discounts.forEach(d => {
                            let val = parseFloat(d);
                            if (!isNaN(val) && val > 0) {
                                finalPrice = finalPrice * (1 - (val / 100));
                            }
                        });
                    }
                    return finalPrice * qty;
                },

                get totals() {
                    const subtotal = this.items.reduce((sum, item) => sum + this.calculateRowTotal(item), 0);
                    let currentTotal = subtotal;
                    let discAmount = 0;

                    if (this.applyDisc) {
                        discAmount = subtotal * ((parseFloat(this.discPercent) || 0) / 100);
                        currentTotal -= discAmount;
                    }
                    if (this.applyRounding) {
                        currentTotal -= (parseFloat(this.roundingAmount) || 0);
                    }

                    // DPP
                    let dpp = currentTotal;
                    if (this.useCustomDPP) {
                        dpp = currentTotal * this.dppFactorCalc; 
                    }

                    // PPN
                    let ppn = 0;
                    if (this.taxId && taxRates[this.taxId]) {
                        const rate = parseFloat(taxRates[this.taxId]);
                        ppn = dpp * (rate / 100);
                    }

                    const grandTotal = currentTotal + ppn + (parseFloat(this.shipping) || 0);

                    return {
                        subtotal: subtotal,
                        discAmount: discAmount,
                        dpp: dpp,
                        ppn: ppn,
                        grandTotal: Math.max(0, Math.round(grandTotal))
                    };
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
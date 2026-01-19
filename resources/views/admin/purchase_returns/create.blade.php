@extends('admin.layouts.app')

@section('title', 'Buat Retur Pembelian')

@section('content')
<div x-data="purchaseReturnForm()" class="pb-20"> 
    
    {{-- 1. HEADER & NAVIGATION --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400 shadow-sm">
                    <i class="material-icons text-xl">assignment_return</i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">Retur Pembelian</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Input pengembalian barang dari PO Completed</p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            {{-- Tambahkan onclick untuk bypass unsaved warning --}}
            <a href="{{ route('admin.purchase-returns.index') }}" 
               onclick="window.isFormDirty = false;" 
               class="btn btn-secondary text-xs h-[38px]">
                <i class="material-icons text-sm mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    <form action="{{ route('admin.purchase-returns.store') }}" method="POST" id="returnForm">
        @csrf

        {{-- 2. SUMBER DATA & OPSI (Split Layout) --}}
        <div class="card p-0 mb-6 overflow-hidden border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="bg-slate-50/80 dark:bg-slate-800/80 px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                    <i class="material-icons text-sm text-indigo-500">tune</i> Konfigurasi Retur
                </h3>
                {{-- Status Loading --}}
                <div x-show="isLoading" class="flex items-center gap-2 text-xs text-indigo-600 font-bold bg-indigo-50 px-3 py-1 rounded-full animate-pulse">
                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Memuat Data...
                </div>
            </div>
            
            <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- KOLOM KIRI: Input Data --}}
                <div class="space-y-5">
                    <div>
                        <label class="form-label label-required text-xs">Pilih Purchase Order</label>
                        <select name="purchase_order_id" id="po_select" class="tom-select" x-model="poId" required>
                            <option value="">Cari No PO / Supplier...</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->po_id }}">
                                    {{ $po->po_number }} • {{ $po->supplier->supplier_name ?? 'Unknown' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1.5 leading-tight">
                            *Hanya menampilkan PO dengan status <strong>Completed</strong> (Barang Diterima).
                        </p>
                    </div>

                    <div>
                        <label class="form-label label-required text-xs">Tanggal Retur</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-slate-400"><i class="material-icons text-sm">calendar_today</i></span>
                            <input type="date" name="return_date" class="form-input pl-9" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: Metode Penanganan (Descriptive Cards) --}}
                <div>
                    <label class="form-label label-required text-xs mb-3">Metode Penanganan Nilai Retur</label>
                    <div class="space-y-3">
                        
                        {{-- Opsi 1: Potong Tagihan --}}
                        <label class="relative flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50 group"
                               :class="handlingType === 'deduct_invoice' ? 'border-indigo-500 bg-indigo-50/30 dark:border-indigo-500 dark:bg-indigo-900/10' : 'border-slate-200 dark:border-slate-700'">
                            
                            <div class="flex items-center h-5 mt-0.5">
                                <input type="radio" name="return_handling_type" value="deduct_invoice" x-model="handlingType" class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="material-icons text-sm" :class="handlingType === 'deduct_invoice' ? 'text-indigo-600' : 'text-slate-400'">remove_circle_outline</i>
                                    <span class="text-sm font-bold" :class="handlingType === 'deduct_invoice' ? 'text-indigo-700 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300'">Potong Tagihan (Invoice)</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Mengurangi sisa hutang pada PO ini secara langsung. <br>
                                    <span class="text-[10px] italic opacity-80">Gunakan jika PO belum lunas atau Anda ingin mengurangi tagihan berjalan.</span>
                                </p>
                            </div>
                        </label>

                        {{-- Opsi 2: Simpan Deposit --}}
                        <label class="relative flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-800/50 group"
                               :class="handlingType === 'store_as_deposit' ? 'border-amber-500 bg-amber-50/30 dark:border-amber-500 dark:bg-amber-900/10' : 'border-slate-200 dark:border-slate-700'">
                            
                            <div class="flex items-center h-5 mt-0.5">
                                <input type="radio" name="return_handling_type" value="store_as_deposit" x-model="handlingType" class="w-4 h-4 text-amber-600 border-slate-300 focus:ring-amber-500">
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="material-icons text-sm" :class="handlingType === 'store_as_deposit' ? 'text-amber-600' : 'text-slate-400'">account_balance_wallet</i>
                                    <span class="text-sm font-bold" :class="handlingType === 'store_as_deposit' ? 'text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300'">Simpan sebagai Deposit</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Nilai retur disimpan sebagai <strong>Deposit Supplier (Aset)</strong>. <br>
                                    <span class="text-[10px] italic opacity-80">Gunakan jika PO sudah lunas, atau saldo ingin dipakai untuk PO lain nanti.</span>
                                </p>
                            </div>
                        </label>

                    </div>
                </div>
            </div>
        </div>

        {{-- 3. DAFTAR BARANG (Full Width Card) --}}
        <div class="card mb-6 border border-slate-200 dark:border-slate-700 shadow-lg overflow-hidden transition-all duration-300" 
             x-show="poId" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4">
            
            {{-- Toolbar Table --}}
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-white dark:bg-slate-800">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    Item Barang
                </h3>
                <div class="flex items-center gap-3">
                    <div class="text-[10px] text-slate-400 font-medium bg-slate-50 px-2 py-1 rounded border border-slate-100">
                        <span class="text-indigo-600 font-bold text-sm" x-text="items.filter(i => i.selected).length">0</span> Dipilih
                    </div>
                </div>
            </div>

            <div class="table-container border-0 rounded-none">
                <table class="table-modern w-full">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 uppercase text-[10px] tracking-wider font-bold">
                        <tr>
                            <th class="w-12 text-center py-3">
                                <input type="checkbox" class="form-check-input" @change="toggleAll($event)">
                            </th>
                            <th class="py-3">Produk</th>
                            <th class="text-right py-3 w-32">Harga Beli</th>
                            <th class="text-center py-3 w-20">Disc (%)</th>
                            <th class="text-center py-3 w-28">Diterima</th>
                            <th class="text-center py-3 w-28">Sisa Stok</th>
                            <th class="text-center py-3 w-32 bg-rose-50/50 text-rose-600">Qty Retur</th>
                            <th class="text-right py-3 w-40">Nilai Retur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800 text-sm">
                        <template x-for="(item, index) in items" :key="item.item_id">
                            <tr class="group transition-all duration-200" 
                                :class="{
                                    'bg-slate-50 opacity-60 pointer-events-none grayscale': isItemEmpty(item), 
                                    'bg-indigo-50/20 dark:bg-indigo-900/10': item.selected,
                                    'hover:bg-slate-50 dark:hover:bg-slate-800/50': !item.selected && !isItemEmpty(item)
                                }">
                                
                                {{-- Checkbox --}}
                                <td class="text-center align-middle py-4">
                                    <input type="checkbox" 
                                           :name="`items[${index}][selected]`" 
                                           class="form-check-input w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 transition-colors"
                                           x-model="item.selected"
                                           :disabled="isItemEmpty(item)">
                                    <input type="hidden" :name="`items[${index}][item_id]`" :value="item.item_id">
                                </td>

                                {{-- Info Produk --}}
                                <td class="align-middle py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 text-sm" x-text="item.product.product_name"></span>
                                        <span class="text-xs text-slate-400 font-mono" x-text="item.product.product_code"></span>
                                        
                                        {{-- Warning Badges --}}
                                        <template x-if="isItemEmpty(item)">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 text-slate-500 text-[10px] font-bold uppercase w-fit mt-1 border border-slate-200">
                                                <i class="material-icons text-[10px]">block</i> Habis Diretur
                                            </span>
                                        </template>
                                        <template x-if="!isItemEmpty(item) && parseFloat(item.quantity_returned) > 0">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-medium w-fit mt-1 border border-amber-100">
                                                <i class="material-icons text-[10px]">history</i> Pernah Retur: <span x-text="formatNumber(item.quantity_returned)"></span>
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                {{-- Harga Beli --}}
                                <td class="align-middle text-right py-4 font-mono text-xs text-slate-600">
                                    <span x-text="formatRupiah(item.price_per_unit)"></span>
                                </td>

                                {{-- Diskon --}}
                                <td class="align-middle text-center py-4">
                                    <div class="flex justify-center flex-wrap gap-1">
                                        <template x-if="item.discounts && item.discounts.length > 0">
                                            <template x-for="disc in item.discounts">
                                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px] border border-slate-200 font-mono" 
                                                      x-text="parseFloat(disc.percentage) + '%'"></span>
                                            </template>
                                        </template>
                                        <span x-show="!item.discounts || item.discounts.length === 0" class="text-slate-300 text-xs">-</span>
                                    </div>
                                </td>

                                {{-- Qty Diterima --}}
                                <td class="align-middle text-center py-4">
                                    <span class="font-bold text-slate-500 text-xs bg-slate-100 px-2 py-1 rounded" x-text="formatNumber(item.quantity)"></span>
                                </td>

                                {{-- Sisa Stok --}}
                                <td class="align-middle text-center py-4">
                                    <span class="badge" 
                                          :class="isItemEmpty(item) ? 'badge-secondary' : 'badge-success'">
                                        <span x-text="formatNumber(item.quantity - item.quantity_returned)"></span>
                                        <span class="text-[9px] ml-0.5" x-text="item.product.unit?.name"></span>
                                    </span>
                                </td>

                                {{-- Input Retur (Highlighted) --}}
                                <td class="align-middle py-3 bg-rose-50/30 p-2">
                                    <div class="relative">
                                        <input type="number" 
                                               :name="`items[${index}][quantity]`" 
                                               x-model="item.return_qty"
                                               @input="calculateRow(index)"
                                               class="form-input text-center font-bold h-9 text-sm transition-all shadow-sm"
                                               :class="{
                                                   'border-rose-500 ring-2 ring-rose-100 text-rose-600': isInvalidQty(item),
                                                   'text-slate-700 border-slate-300 focus:border-indigo-500': !isInvalidQty(item) && item.selected,
                                                   'bg-slate-100 text-slate-400 cursor-not-allowed border-slate-200': !item.selected
                                               }"
                                               min="0" step="0.01"
                                               :max="item.quantity - item.quantity_returned"
                                               :disabled="!item.selected"
                                               placeholder="0">
                                        
                                        {{-- Error Tooltip --}}
                                        <div x-show="isInvalidQty(item)" 
                                             class="absolute left-0 -bottom-5 w-full text-center z-10"
                                             x-transition.opacity>
                                            <span class="text-[9px] font-bold text-rose-600 bg-white border border-rose-200 px-2 py-0.5 rounded shadow-sm">
                                                Maks: <span x-text="formatNumber(item.quantity - item.quantity_returned)"></span>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Nilai Retur --}}
                                <td class="align-middle text-right py-4 pr-6">
                                    <div class="font-mono font-bold text-sm transition-colors duration-300" 
                                         :class="item.selected ? 'text-rose-600' : 'text-slate-300'" 
                                         x-text="formatRupiah(item.return_subtotal)"></div>
                                </td>
                            </tr>
                        </template>
                        
                        {{-- Empty State --}}
                        <template x-if="items.length === 0 && !isLoading">
                            <tr>
                                <td colspan="8" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-50">
                                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                            <i class="material-icons text-3xl text-slate-400">find_in_page</i>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">Pilih Purchase Order di atas untuk memuat barang.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 4. FOOTER SUMMARY (Grid Layout) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-show="poId" x-transition>
            
            {{-- Kiri: Catatan --}}
            <div class="card p-6 bg-white dark:bg-slate-800">
                <label class="form-label text-xs uppercase text-slate-400 font-bold mb-2 flex items-center gap-2">
                    <i class="material-icons text-sm">edit_note</i> Catatan Retur
                </label>
                <textarea name="notes" rows="5" class="form-textarea w-full text-sm leading-relaxed border-slate-200 bg-slate-50 focus:bg-white transition-colors" 
                          placeholder="Jelaskan alasan pengembalian (Barang Rusak, Expired, Salah Kirim, dll)..."></textarea>
            </div>

            {{-- Kanan: Kalkulasi --}}
            <div class="card overflow-hidden border border-slate-200 dark:border-slate-700">
                <div class="p-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-sm text-indigo-500">calculate</i> Estimasi Pengembalian Dana
                    </h3>
                </div>
                
                <div class="p-6 space-y-3 bg-white dark:bg-slate-800">
                    {{-- Subtotal --}}
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500">Subtotal Item</span>
                        <span class="font-mono font-bold text-slate-700 dark:text-slate-200" x-text="formatRupiah(summary.subtotal)"></span>
                    </div>

                    {{-- Diskon --}}
                    <div class="flex justify-between items-center text-sm transition-all" x-show="poData.disc_fee_percent > 0">
                        <span class="text-slate-500 text-xs flex items-center gap-1">
                            <i class="material-icons text-[12px] text-amber-500">percent</i>
                            <span x-text="'Proporsi Diskon PO (' + poData.disc_fee_percent + '%)'"></span>
                        </span>
                        <span class="font-mono text-rose-500 font-medium" x-text="'- ' + formatRupiah(summary.disc_fee_allocation)"></span>
                    </div>

                    {{-- Pajak --}}
                    <div class="flex justify-between items-center text-sm transition-all pt-2 border-t border-dashed border-slate-100" x-show="poData.tax_rate > 0">
                        <span class="text-slate-500 text-xs">DPP Retur</span>
                        <span class="font-mono text-slate-500" x-text="formatRupiah(summary.dpp)"></span>
                    </div>
                    <div class="flex justify-between items-center text-sm transition-all" x-show="poData.tax_rate > 0">
                        <span class="text-slate-500 text-xs flex items-center gap-1">
                            <i class="material-icons text-[12px] text-blue-500">account_balance</i>
                            <span x-text="'Pajak (' + poData.tax_rate + '%)'"></span>
                        </span>
                        <span class="font-mono text-indigo-600 font-medium" x-text="'+ ' + formatRupiah(summary.ppn)"></span>
                    </div>

                    {{-- Total --}}
                    <div class="flex justify-between items-center pt-4 border-t-2 border-slate-100 dark:border-slate-700 mt-2">
                        <span class="text-base font-bold text-slate-700 dark:text-white uppercase tracking-tight">Total Nilai Retur</span>
                        <span class="text-2xl font-bold font-mono text-rose-600" x-text="formatRupiah(summary.total_return)"></span>
                    </div>
                </div>
                
                {{-- Action Button --}}
                <div class="p-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                    <button type="button" @click="submitForm" 
                            class="btn btn-primary w-full md:w-auto shadow-lg shadow-indigo-500/20 py-2.5 px-6"
                            :disabled="isLoading || !hasSelectedItems()">
                        <i class="material-icons text-[18px] mr-2">check_circle</i> Simpan Transaksi Retur
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
    function purchaseReturnForm() {
        return {
            poId: '{{ $preselectedPoId ?? "" }}', 
            items: [],
            // Default variable
            handlingType: 'deduct_invoice', // Default value
            poData: {
                disc_fee_percent: 0,
                tax_rate: 0,
                use_custom_dpp: false,
                custom_dpp_factor: 1
            }, 
            summary: {
                subtotal: 0,
                disc_fee_allocation: 0,
                dpp: 0,
                ppn: 0,
                total_return: 0
            },
            isLoading: false,

            init() {
                if (this.poId) {
                    this.$nextTick(() => {
                        const selectEl = document.getElementById('po_select');
                        if(selectEl && selectEl.tomselect) {
                            selectEl.tomselect.setValue(this.poId);
                        }
                        this.fetchData(this.poId);
                    });
                }

                this.$watch('poId', (value) => {
                    if (value) {
                        this.fetchData(value);
                    } else {
                        this.items = [];
                        this.resetPoData();
                    }
                });
            },

            resetPoData() {
                this.poData = {
                    disc_fee_percent: 0,
                    tax_rate: 0,
                    use_custom_dpp: false,
                    custom_dpp_factor: 1
                };
            },

            fetchData(poId) {
                this.isLoading = true;
                this.items = []; 
                this.resetPoData();

                fetch(`/admin/api/purchase-orders/${poId}/items`)
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal mengambil data');
                        return response.json();
                    })
                    .then(data => {
                        if (data.po) {
                            this.poData = {
                                disc_fee_percent: parseFloat(data.po.disc_fee_percent || 0),
                                tax_rate: parseFloat(data.po.tax_rate || 0),
                                use_custom_dpp: !!data.po.use_custom_dpp,
                                custom_dpp_factor: parseFloat(data.po.custom_dpp_factor || 1)
                            };
                        }

                        this.items = data.items.map(item => {
                            let netPrice = parseFloat(item.price_per_unit);
                            if (parseFloat(item.quantity) > 0 && parseFloat(item.subtotal) > 0) {
                                netPrice = parseFloat(item.subtotal) / parseFloat(item.quantity);
                            }

                            return {
                                ...item,
                                selected: false,
                                return_qty: 0,
                                return_subtotal: 0,
                                net_price: netPrice 
                            };
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if(window.showToast) window.showToast('Gagal mengambil data PO.', 'error');
                    })
                    .finally(() => {
                        this.isLoading = false;
                    });
            },

            toggleAll(e) {
                const checked = e.target.checked;
                this.items.forEach(item => {
                    if (!this.isItemEmpty(item)) {
                        item.selected = checked;
                    }
                });
                this.calculateSummary();
            },

            isItemEmpty(item) {
                return (parseFloat(item.quantity) - parseFloat(item.quantity_returned)) <= 0.001;
            },

            isInvalidQty(item) {
                const max = parseFloat(item.quantity) - parseFloat(item.quantity_returned);
                const current = parseFloat(item.return_qty);
                return current > max || (item.selected && current <= 0);
            },

            hasSelectedItems() {
                return this.items.some(item => item.selected && parseFloat(item.return_qty) > 0 && !this.isInvalidQty(item));
            },

            calculateRow(index) {
                const item = this.items[index];
                if (item.selected) {
                    item.return_subtotal = parseFloat(item.return_qty) * item.net_price;
                } else {
                    item.return_subtotal = 0;
                }
                this.calculateSummary();
            },

            calculateSummary() {
                let subtotal = 0;
                this.items.forEach(item => {
                    if (item.selected && !this.isInvalidQty(item)) {
                        const qty = parseFloat(item.return_qty) || 0;
                        item.return_subtotal = qty * item.net_price;
                        subtotal += item.return_subtotal;
                    }
                });
                
                this.summary.subtotal = subtotal;

                let amountAfterDisc = subtotal;
                if (this.poData.disc_fee_percent > 0) {
                    this.summary.disc_fee_allocation = subtotal * (this.poData.disc_fee_percent / 100);
                    amountAfterDisc -= this.summary.disc_fee_allocation;
                } else {
                    this.summary.disc_fee_allocation = 0;
                }

                let dpp = amountAfterDisc;
                if (this.poData.use_custom_dpp) {
                    dpp = amountAfterDisc * this.poData.custom_dpp_factor;
                }
                this.summary.dpp = dpp;

                this.summary.ppn = 0;
                if (this.poData.tax_rate > 0) {
                    this.summary.ppn = dpp * (this.poData.tax_rate / 100);
                }

                this.summary.total_return = amountAfterDisc + this.summary.ppn;
            },

            submitForm(e) {
                if (!this.hasSelectedItems()) {
                    if(window.showToast) window.showToast('Pilih minimal satu barang.', 'error');
                    return;
                }
                if (this.items.some(item => item.selected && this.isInvalidQty(item))) {
                    if(window.showToast) window.showToast('Cek jumlah barang.', 'error');
                    return;
                }

                if (typeof window.isFormDirty !== 'undefined') {
                    window.isFormDirty = false;
                }

                document.getElementById('returnForm').submit();
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
            },

            formatNumber(value) {
                return parseFloat(value).toLocaleString('id-ID');
            }
        }
    }
</script>
@endpush
@endsection
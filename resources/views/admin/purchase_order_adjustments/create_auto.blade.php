@extends('admin.layouts.app')

@section('title', 'Koreksi Otomatis Purchase Order')

@section('content')
<div x-data="poAdjustmentAuto()" class="flex flex-col gap-6">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Koreksi & Revisi PO</h1>
                <span class="px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold uppercase">
                    #{{ $purchaseOrder->po_number }}
                </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Ubah data pesanan selengkapnya. Sistem otomatis mendeteksi selisih (Nota Debet/Kredit).
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.purchase-orders.show', $purchaseOrder->po_id) }}" 
               class="btn btn-secondary h-[42px] w-[42px] p-0 flex items-center justify-center rounded-lg shadow-sm hover:bg-slate-50 transition-colors" 
               title="Kembali">
                <i class="material-icons text-[22px]">arrow_back</i>
            </a>
        </div>
    </div>

    {{-- INFO ALERT SMART LOGIC --}}
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="material-icons text-blue-500">auto_fix_high</i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Mode Penyesuaian Pintar:</strong> 
                    Silahkan edit data di bawah ini seolah-olah Anda mengedit PO biasa (Item, Harga, Pajak, dll).
                    <br>
                    Sistem akan menghitung selisih pembayaran dan jurnal secara otomatis di bagian bawah halaman.
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.purchase-order-adjustments.store.auto', $purchaseOrder->po_id) }}" method="POST" id="adjustmentForm" @submit.prevent="submitForm">
        @csrf

        {{-- MAIN CARD --}}
        <div class="card shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden relative">
            
            {{-- Loading Overlay --}}
            <div x-show="isLoading" class="absolute inset-0 bg-white/80 dark:bg-slate-800/80 z-50 flex items-center justify-center backdrop-blur-sm" style="display: none;">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-3"></div>
                    <span class="text-sm font-bold text-indigo-600">Memproses Koreksi...</span>
                </div>
            </div>

            {{-- 1. INFORMASI PESANAN --}}
            <div class="p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-5 flex items-center gap-2">
                    <i class="material-icons text-sm text-indigo-500">info</i> Informasi Utama
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    {{-- Supplier (Readonly saat Adjustment) --}}
                    <div class="md:col-span-4">
                        <label class="form-label label-required">Supplier</label>
                        <div class="relative">
                            <input type="text" value="{{ $purchaseOrder->supplier->supplier_name }}" class="form-input bg-slate-100 text-slate-500 cursor-not-allowed" readonly>
                            <input type="hidden" name="supplier_id" value="{{ $purchaseOrder->supplier_id }}" x-model="supplierId">
                            <i class="material-icons absolute right-3 top-2.5 text-slate-400 text-lg">lock</i>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 italic">*Supplier tidak dapat diubah saat melakukan koreksi.</p>
                    </div>

                    {{-- Tanggal --}}
                    <div class="md:col-span-2">
                        <label class="form-label label-required">Tgl Order</label>
                        <input type="date" name="order_date" class="form-input" x-model="formData.order_date" required>
                    </div>

                    {{-- Jatuh Tempo --}}
                    <div class="md:col-span-2">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-input" x-model="formData.due_date">
                    </div>

                    {{-- Requester --}}
                    <div class="md:col-span-4">
                        <label class="form-label">Requester</label>
                        <select name="requester_user_id" class="tom-select" x-model="formData.requester_id">
                            <option value="">Pilih User...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->user_id }}" {{ $purchaseOrder->requester_user_id == $user->user_id ? 'selected' : '' }}>
                                    {{ $user->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 w-full"></div>

            {{-- 2. ITEM BARANG --}}
            <div class="p-0">
                <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/30 flex flex-col md:flex-row justify-between items-center gap-4">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                        <i class="material-icons text-sm text-indigo-500">shopping_cart</i> Daftar Barang
                    </h3>
                    
                    {{-- Bulk Discount Tool --}}
                    <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="px-2 text-[10px] font-bold text-slate-400 uppercase">Bulk Diskon:</div>
                        <input type="text" x-model="bulkDiscountValue" 
                               class="form-input py-1 px-2 text-xs h-8 w-28 border-slate-200 focus:border-indigo-500" 
                               placeholder="Ex: 30+5">
                        
                        <button type="button" @click="applyBulkDiscount('all')" 
                                class="px-3 py-1.5 text-[10px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100 rounded hover:bg-indigo-100 transition-colors">
                            Ke Semua
                        </button>
                        
                        <button type="button" @click="applyBulkDiscount('selected')" 
                                class="px-3 py-1.5 text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded hover:bg-emerald-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="selectedItems.length === 0">
                            Ke Terpilih
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-b border-slate-200 dark:border-slate-700">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="w-10 px-4 py-3 text-center">
                                    <input type="checkbox" class="form-check-input" @change="toggleSelectAll($event)">
                                </th>
                                <th class="px-4 py-3 min-w-[250px]">Produk</th>
                                <th class="px-4 py-3 min-w-[150px] text-right">Harga Satuan</th>
                                <th class="px-4 py-3 w-[160px] text-center">Qty / Unit</th>
                                <th class="px-4 py-3 min-w-[200px]">Diskon (%)</th>
                                <th class="px-4 py-3 min-w-[150px] text-right">Subtotal</th>
                                <th class="px-4 py-3 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr class="group hover:bg-indigo-50/10 transition-colors">
                                    {{-- Checkbox --}}
                                    <td class="text-center align-top pt-4">
                                        <input type="checkbox" class="form-check-input" :value="item.id" x-model="selectedItems">
                                        <input type="hidden" :name="`products[${index}][item_id]`" :value="item.item_id_db">
                                    </td>

                                    {{-- Produk --}}
                                    <td class="px-4 py-3 align-top">
                                        <select :name="`products[${index}][product_id]`" 
                                                class="tom-select-product w-full"
                                                :id="`product-select-${item.id}`"
                                                x-init="initProductSelect($el, index)"
                                                required>
                                            <option value="">Pilih Produk...</option>
                                        </select>
                                        <div class="mt-1 text-[10px] text-slate-400 font-mono" x-show="item.product_id">
                                            Kode: <span x-text="item.code || '-'"></span>
                                        </div>
                                    </td>

                                    {{-- Harga (PERBAIKAN HOVER DI SINI) --}}
                                    <td class="px-4 py-3 align-top">
                                        <div class="relative">
                                            <span class="absolute left-3 top-2.5 text-slate-400 text-xs font-bold">Rp</span>
                                            {{-- HAPUS x-model, GUNAKAN x-init SAJA --}}
                                            <input type="text" 
                                                   class="form-input pl-8 text-right font-mono text-sm autonumeric"
                                                   x-init="initAutoNumeric($el, index, 'price')"
                                                   autocomplete="off">
                                            {{-- Hidden input untuk kirim data ke server --}}
                                            <input type="hidden" :name="`products[${index}][price_per_unit]`" :value="item.price">
                                        </div>
                                        <div class="mt-2 flex justify-end">
                                            <label class="inline-flex items-center gap-2 cursor-pointer group/chk">
                                                <input type="checkbox" :name="`products[${index}][update_master_price]`" value="1" x-model="item.update_master_price"
                                                       class="w-3.5 h-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-[10px] text-slate-400 group-hover/chk:text-indigo-600 transition-colors">
                                                    Update Master
                                                </span>
                                            </label>
                                        </div>
                                    </td>

                                    {{-- Qty --}}
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex items-center w-full">
                                            <input type="number" :name="`products[${index}][quantity]`" 
                                                x-model="item.quantity"
                                                @input="calculateRow(index)"
                                                class="form-input text-center rounded-r-none z-10 w-full font-bold px-1" 
                                                min="0" step="any" placeholder="0" required>
                                            <div class="bg-slate-100 dark:bg-slate-700 border border-l-0 border-slate-300 dark:border-slate-600 rounded-r px-2 py-2.5 text-xs font-bold text-slate-500 h-[42px] flex items-center justify-center min-w-[50px]">
                                                <span x-text="item.unit || '-'"></span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Diskon --}}
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-wrap items-center gap-1.5 p-1 border border-dashed border-slate-200 rounded bg-slate-50/50 min-h-[42px]">
                                            <template x-for="(disc, dIndex) in item.discounts" :key="dIndex">
                                                <div class="relative flex items-center group/disc animate-enter">
                                                    <input type="number" step="0.01" min="0" max="100"
                                                           x-model="item.discounts[dIndex]"
                                                           @input="calculateRow(index)"
                                                           class="form-input text-center w-[48px] h-[34px] text-xs p-1 font-mono focus:ring-1 focus:ring-indigo-500"
                                                           placeholder="%">
                                                    
                                                    <button type="button" @click="removeDiscount(index, dIndex)"
                                                            class="absolute -top-1.5 -right-1.5 bg-white text-rose-500 border border-rose-200 rounded-full w-4 h-4 flex items-center justify-center shadow-sm opacity-0 group-hover/disc:opacity-100 transition-opacity z-10 hover:bg-rose-50">
                                                        <span class="text-[10px] font-bold leading-none">&times;</span>
                                                    </button>
                                                    <input type="hidden" :name="`products[${index}][discounts][]`" :value="disc">
                                                    <span x-show="dIndex < item.discounts.length - 1" class="text-slate-300 mx-0.5 text-xs font-bold">+</span>
                                                </div>
                                            </template>
                                            <button type="button" @click="addDiscountLevel(index)" 
                                                    class="w-[34px] h-[34px] rounded border border-dashed border-slate-300 hover:border-indigo-500 text-slate-400 hover:text-indigo-600 flex items-center justify-center transition-colors bg-white">
                                                <i class="material-icons text-[16px]">add</i>
                                            </button>
                                        </div>
                                    </td>

                                    {{-- Subtotal --}}
                                    <td class="px-4 py-3 align-top text-right">
                                        <div class="font-mono font-bold text-slate-700 dark:text-slate-200 text-sm pt-2" 
                                             x-text="formatMoney(item.subtotal)"></div>
                                    </td>

                                    {{-- Hapus --}}
                                    <td class="px-4 py-3 align-top text-center pt-2">
                                        <button type="button" @click="removeItem(index)" 
                                                class="w-8 h-8 rounded-full hover:bg-rose-50 text-slate-300 hover:text-rose-500 flex items-center justify-center transition-colors">
                                            <i class="material-icons text-[20px]">delete_outline</i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Footer Tabel --}}
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50" x-show="supplierId">
                    <button type="button" @click="addItem()" class="btn btn-secondary btn-sm border-dashed w-full sm:w-auto shadow-sm hover:bg-white">
                        <i class="material-icons text-[16px] mr-1">add_circle_outline</i> Tambah Baris Barang
                    </button>
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-700 w-full"></div>

            {{-- 3. SUMMARY & SMART COMPARISON --}}
            <div class="grid grid-cols-1 lg:grid-cols-2">
                
                {{-- Kolom Kiri: Catatan & Perbandingan Detail --}}
                <div class="p-6 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="material-icons text-sm text-indigo-500">analytics</i> Analisa Perubahan
                    </h3>

                    {{-- Comparison Box --}}
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm space-y-3 mb-6">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Total Tagihan Awal</span>
                            <span class="font-mono font-bold text-slate-500 line-through" x-text="formatMoney(originalGrandTotal)"></span>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-700 dark:text-white font-bold">Total Tagihan Revisi</span>
                            <span class="font-mono font-bold text-indigo-600 text-lg" x-text="formatMoney(summary.grand_total)"></span>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-700 my-2"></div>

                        <div class="flex justify-between items-center p-3 rounded-lg transition-colors duration-300"
                             :class="{
                                 'bg-emerald-50 border border-emerald-200': diff > 0, 
                                 'bg-amber-50 border border-amber-200': diff < 0,
                                 'bg-slate-50': diff == 0
                             }">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold uppercase" 
                                      :class="diff > 0 ? 'text-emerald-700' : (diff < 0 ? 'text-amber-700' : 'text-slate-500')"
                                      x-text="getDiffLabel()"></span>
                                
                                <template x-if="diff > 0">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-200 text-emerald-800">NOTA KREDIT</span>
                                </template>
                                <template x-if="diff < 0">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-800">NOTA DEBIT</span>
                                </template>
                            </div>
                            <span class="font-mono font-extrabold text-lg" 
                                  :class="diff > 0 ? 'text-emerald-600' : (diff < 0 ? 'text-amber-600' : 'text-slate-400')"
                                  x-text="formatMoney(Math.abs(diff))"></span>
                        </div>
                    </div>

                    {{-- SMART ACTION SECTION --}}
                    <div x-show="Math.abs(diff) > 1" x-transition.opacity class="animate-enter mb-6">
                        {{-- KASUS A: TAGIHAN BERKURANG (Nota Kredit) --}}
                        <div x-show="diff > 0">
                            <label class="form-label label-required text-emerald-600 mb-3 block">
                                <i class="material-icons text-sm align-middle mr-1">savings</i>
                                Penanganan Kelebihan Bayar
                            </label>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="relative flex items-start p-3 border rounded-xl cursor-pointer hover:bg-emerald-50 hover:border-emerald-300 transition-all"
                                       :class="overpaymentAction === 'deposit' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200'">
                                    <div class="flex items-center h-5">
                                        <input type="radio" name="overpayment_action" value="deposit" x-model="overpaymentAction" class="form-radio text-emerald-600 w-4 h-4">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="block font-bold text-slate-800 dark:text-white">Simpan sebagai Deposit</span>
                                        <span class="block text-xs text-slate-500 mt-1">Saldo akan disimpan untuk memotong tagihan berikutnya.</span>
                                    </div>
                                </label>

                                <label class="relative flex items-start p-3 border rounded-xl cursor-pointer hover:bg-emerald-50 hover:border-emerald-300 transition-all"
                                       :class="overpaymentAction === 'refund' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200'">
                                    <div class="flex items-center h-5">
                                        <input type="radio" name="overpayment_action" value="refund" x-model="overpaymentAction" class="form-radio text-emerald-600 w-4 h-4">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="block font-bold text-slate-800 dark:text-white">Refund Manual / Potong Invoice</span>
                                        <span class="block text-xs text-slate-500 mt-1">Terima Uang Kembali (Kas Masuk). Nilai AP di-nolkan lewat jurnal adjustment.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- KASUS B: TAGIHAN BERTAMBAH (Nota Debit) --}}
                        <div x-show="diff < 0" class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
                            <i class="material-icons text-amber-500">warning</i>
                            <div>
                                <h4 class="text-sm font-bold text-amber-800">Tagihan Bertambah (Kurang Bayar)</h4>
                                <p class="text-xs text-amber-700 mt-1">
                                    Hutang ke supplier akan bertambah otomatis. Tidak ada opsi deposit/refund yang diperlukan.
                                </p>
                                <input type="hidden" name="overpayment_action" value="deposit"> 
                            </div>
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="form-label label-required">Alasan Penyesuaian</label>
                        <textarea name="notes" rows="4" class="form-textarea w-full text-sm leading-relaxed border-slate-200" 
                                  placeholder="Wajib diisi: Contoh: Revisi harga dari supplier..." required></textarea>
                    </div>
                </div>

                {{-- Kolom Kanan: Kalkulasi Lengkap --}}
                <div class="p-6 bg-slate-50 dark:bg-slate-900/50">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="material-icons text-sm text-indigo-500">calculate</i> Kalkulasi
                    </h3>

                    <div class="space-y-4">
                        {{-- Subtotal --}}
                        <div class="flex justify-between items-center text-sm pb-2 border-b border-dashed border-slate-200">
                            <span class="text-slate-500">Subtotal Item</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-200" x-text="formatMoney(summary.subtotal)"></span>
                            <input type="hidden" name="subtotal" :value="summary.subtotal">
                        </div>

                        {{-- Diskon Akhir --}}
                        <div class="flex justify-between items-center gap-4 text-sm group">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="apply_disc_fee" value="1" x-model="summary.apply_disc_fee" class="form-check-input">
                                <span class="text-slate-600">Diskon Akhir / Fee (%)</span>
                            </label>
                            <div class="flex items-center justify-end gap-2 w-1/2">
                                <input type="number" name="disc_fee_percent" x-model="summary.disc_fee_percent" 
                                       :disabled="!summary.apply_disc_fee" @input="calculateSummary()"
                                       class="form-input py-1 px-2 text-right h-8 w-16 text-xs" :class="!summary.apply_disc_fee ? 'opacity-50' : 'bg-white'" placeholder="0">
                                <span class="font-mono text-rose-500 min-w-[80px] text-right" x-text="summary.disc_fee_amount > 0 ? '- ' + formatMoney(summary.disc_fee_amount) : 'Rp 0'"></span>
                            </div>
                        </div>

                        {{-- Pembulatan (PERBAIKAN HOVER DI SINI JUGA) --}}
                        <div class="flex justify-between items-center gap-4 text-sm group">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="apply_rounding_discount" value="1" x-model="summary.apply_rounding" class="form-check-input">
                                <span class="text-slate-600">Potongan Pembulatan</span>
                            </label>
                            <div class="w-1/2 flex justify-end">
                                <input type="text" class="form-input py-1 px-2 text-right h-8 w-36 text-xs autonumeric"
                                       :disabled="!summary.apply_rounding"
                                       :class="!summary.apply_rounding ? 'opacity-50' : 'bg-white'"
                                       x-init="initAutoNumeric($el, null, 'rounding')"
                                       autocomplete="off">
                                <input type="hidden" name="rounding_discount_amount" :value="summary.rounding">
                            </div>
                        </div>

                        {{-- DPP & PPN Section --}}
                        <div class="bg-indigo-50/50 dark:bg-indigo-900/10 p-3 rounded-lg border border-indigo-100 dark:border-indigo-900/30">
                            <div class="flex justify-between items-center text-sm mb-2">
                                <span class="text-slate-500 font-bold text-xs">DPP (Dasar Pengenaan Pajak)</span>
                                <span class="font-mono font-bold text-slate-700" x-text="formatMoney(summary.dpp)"></span>
                            </div>
                            
                            {{-- Custom DPP --}}
                            <div class="flex justify-between items-center gap-2 mt-1 mb-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="use_custom_dpp_factor" value="1" x-model="summary.use_custom_dpp" @change="toggleDppFactor()" class="form-check-input w-3 h-3">
                                    <span class="text-[10px] text-indigo-700">Faktor DPP (Pecahan)</span>
                                </label>
                                <input type="text" name="custom_dpp_factor_input" x-model="summary.dpp_factor_input" @blur="evaluateFraction()"
                                       :disabled="!summary.use_custom_dpp" class="form-input py-0.5 px-2 text-center text-xs h-6 w-16" placeholder="11/12">
                                <input type="hidden" name="custom_dpp_factor" :value="summary.dpp_factor_value">
                            </div>

                            <div class="border-t border-indigo-200 my-2"></div>

                            {{-- PPN Select --}}
                            <div class="flex justify-between items-center gap-4 text-sm mt-3">
                                <div class="w-48">
                                    <label class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Pajak (PPN)</label>
                                    <select name="tax_id" id="tax_select" class="tom-select-tax w-full" required>
                                        <option value="">Tanpa Pajak</option>
                                        @foreach($taxes as $tax)
                                            <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex flex-col items-end flex-1">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 mb-1">Nilai Pajak</span>
                                    <span class="font-mono font-bold text-slate-700 min-w-[80px] text-right" x-text="'+ ' + formatMoney(summary.ppn)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Ongkir (PERBAIKAN HOVER DI SINI JUGA) --}}
                        <div class="flex justify-between items-center gap-4 text-sm pt-2">
                            <span class="text-slate-500 font-medium">Biaya Kirim / Lainnya</span>
                            <div class="w-1/2 flex justify-end items-center gap-2">
                                <span class="text-xs text-slate-400 font-bold">+</span>
                                <input type="text" class="form-input py-1 px-2 text-right h-8 w-36 text-xs autonumeric"
                                       x-init="initAutoNumeric($el, null, 'shipping')"
                                       autocomplete="off">
                                <input type="hidden" name="shipping_amount" :value="summary.shipping">
                            </div>
                        </div>

                        {{-- Grand Total --}}
                        <div class="flex justify-between items-center pt-4 border-t-2 border-slate-800 dark:border-white mt-2">
                            <div>
                                <span class="text-lg font-extrabold text-slate-900 dark:text-white">GRAND TOTAL</span>
                            </div>
                            <span class="text-2xl font-bold font-mono text-indigo-600 dark:text-indigo-400" x-text="formatMoney(summary.grand_total)"></span>
                            <input type="hidden" name="grand_total" :value="summary.grand_total">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ACTIONS FOOTER --}}
        <div class="flex justify-end pt-6 pb-10 gap-3">
            <a href="{{ route('admin.purchase-orders.show', $purchaseOrder->po_id) }}" class="btn btn-secondary btn-lg px-6">
                Batal
            </a>
            <button type="submit" class="btn btn-primary btn-lg px-8 shadow-xl shadow-indigo-500/20 transform hover:-translate-y-1 transition-all"
                    :disabled="isLoading || Math.abs(diff) < 1">
                <i class="material-icons mr-2 text-[20px]">save</i> Simpan Koreksi
            </button>
        </div>
    </form>
</div>

{{-- DATA MASTER (Produk & Pajak) --}}
<script>
    // Data Master Produk (Hanya milik Supplier ini)
    window.allProducts = [
        @foreach($products->where('supplier_id', $purchaseOrder->supplier_id) as $p)
        {
            id: "{{ $p->product_id }}",
            supplier_id: "{{ $p->supplier_id }}",
            code: "{{ $p->product_code }}",
            name: "{{ addslashes($p->product_name) }}",
            price: {{ $p->purchase_price ?? 0 }},
            unit: "{{ $p->unit->name ?? 'Pcs' }}"
        },
        @endforeach
    ];

    window.taxes = [
        @foreach($taxes as $t)
        { id: "{{ $t->id }}", name: "{{ $t->name }}", rate: {{ $t->rate }} },
        @endforeach
    ];
</script>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('poAdjustmentAuto', () => ({
            isLoading: false,
            
            // --- INJECT DATA DB ---
            supplierId: "{{ $purchaseOrder->supplier_id }}",
            originalGrandTotal: {{ $purchaseOrder->grand_total }},

            formData: {
                order_date: '{{ $purchaseOrder->order_date->format("Y-m-d") }}',
                due_date: '{{ $purchaseOrder->due_date ? $purchaseOrder->due_date->format("Y-m-d") : "" }}',
                requester_id: '{{ $purchaseOrder->requester_user_id }}',
            },

            // State Smart Logic
            diff: 0,
            overpaymentAction: 'deposit',

            // Inject Items
            items: [
                @foreach($purchaseOrder->items as $item)
                {
                    id: Date.now() + {{ $loop->index }},
                    item_id_db: "{{ $item->item_id }}",
                    product_id: "{{ $item->product_id }}",
                    product_name: "{{ addslashes($item->product->product_name) }}",
                    product_code: "{{ $item->product->product_code }}",
                    unit: "{{ $item->product->unit->name ?? 'Pcs' }}",
                    price: {{ $item->price_per_unit }}, 
                    // price_display tidak perlu diisi manual, autonumeric yang akan isi via init
                    quantity: {{ $item->quantity }},
                    update_master_price: false,
                    discounts: @json($item->discounts->pluck('percentage') ?? []), 
                    subtotal: {{ $item->subtotal }} 
                },
                @endforeach
            ],

            summary: {
                subtotal: {{ $purchaseOrder->subtotal }},
                apply_disc_fee: {{ $purchaseOrder->apply_disc_fee ? 'true' : 'false' }},
                disc_fee_percent: {{ $purchaseOrder->disc_fee_percent ?? 0 }},
                disc_fee_amount: {{ $purchaseOrder->disc_fee_amount ?? 0 }},
                apply_rounding: {{ $purchaseOrder->apply_rounding_discount ? 'true' : 'false' }},
                rounding: {{ $purchaseOrder->rounding_discount_amount ?? 0 }},
                use_custom_dpp: {{ $purchaseOrder->use_custom_dpp_factor ? 'true' : 'false' }},
                dpp_factor_input: '{{ $purchaseOrder->custom_dpp_factor == 0.91666667 ? "11/12" : $purchaseOrder->custom_dpp_factor }}',
                dpp_factor_value: {{ $purchaseOrder->custom_dpp_factor ?? 1 }}, 
                dpp: {{ $purchaseOrder->dpp ?? 0 }},
                tax_id: '{{ $purchaseOrder->tax_id }}',
                ppn: {{ $purchaseOrder->ppn ?? 0 }},
                shipping: {{ $purchaseOrder->shipping_amount ?? 0 }},
                grand_total: {{ $purchaseOrder->grand_total }}
            },
            
            filteredProducts: [],
            selectedItems: [],
            bulkDiscountValue: '',

            init() {
                if(this.supplierId) {
                    this.filteredProducts = window.allProducts.filter(p => p.supplier_id == this.supplierId);
                }
                this.initTaxSelect();
                this.$nextTick(() => { this.calculateSummary(); });
            },

            // --- PERBAIKAN LOGIC AUTONUMERIC ---
            initAutoNumeric(el, index, type) {
                if (typeof AutoNumeric === 'undefined') return;
                if (AutoNumeric.getAutoNumericElement(el)) return;
                
                // Konfigurasi agar tidak "loncat" saat hover/focus
                const options = {
                    ...window.defaultAutoNumericOptions,
                    modifyValueOnWheel: false,
                    watchExternalChanges: true, 
                };
                
                const an = new AutoNumeric(el, options);
                
                // Set nilai awal
                let initialVal = 0;
                if(type === 'price') initialVal = this.items[index].price;
                if(type === 'rounding') initialVal = this.summary.rounding;
                if(type === 'shipping') initialVal = this.summary.shipping;
                
                an.set(initialVal);

                // Event Listener khusus (Raw Value)
                el.addEventListener('autoNumeric:rawValueModified', e => {
                    const val = parseFloat(e.detail.newRawValue || 0);
                    if (type === 'price') {
                        // Update data di Alpine tanpa memicu re-render DOM input
                        this.items[index].price = val; 
                        this.calculateRow(index);
                    } else if (type === 'rounding') {
                        this.summary.rounding = val;
                        this.calculateSummary();
                    } else if (type === 'shipping') {
                        this.summary.shipping = val;
                        this.calculateSummary();
                    }
                });
            },

            // Pastikan fungsi ini kosong agar tidak konflik dengan x-model
            updateItemPrice(index, val) {}, 
            updateSummaryAmount(type, val) {},

            // --- SISA LOGIC SAMA ---
            initTaxSelect() {
                const el = document.getElementById('tax_select');
                if(!el) return;
                if (el.tomselect) el.tomselect.destroy();
                new TomSelect(el, {
                    ...window.defaultTomSelectConfig,
                    placeholder: 'Pilih Pajak...',
                    allowEmptyOption: true,
                    onChange: (value) => {
                        this.summary.tax_id = value;
                        this.calculateSummary();
                    }
                }).setValue(this.summary.tax_id, true);
            },

            initProductSelect(el, index) {
                if (typeof TomSelect === 'undefined') return;
                if (el.tomselect) el.tomselect.destroy();
                let optionsHtml = '<option value="">Pilih Produk...</option>';
                this.filteredProducts.forEach(p => {
                    const isSelected = this.items[index].product_id == p.id ? 'selected' : '';
                    optionsHtml += `<option value="${p.id}" ${isSelected}>${p.code} - ${p.name}</option>`;
                });
                el.innerHTML = optionsHtml;
                new TomSelect(el, {
                    ...window.defaultTomSelectConfig,
                    placeholder: 'Cari Produk...',
                    onChange: (value) => {
                        this.items[index].product_id = value;
                        const product = this.filteredProducts.find(p => p.id == value);
                        if (product) {
                            this.items[index].unit = product.unit;
                            this.items[index].code = product.code;
                            this.items[index].product_name = product.name;
                            this.items[index].price = parseFloat(product.price);
                            
                            // Set AutoNumeric Value
                            const row = el.closest('tr');
                            const priceInput = row.querySelector('.autonumeric');
                            if(priceInput && AutoNumeric.getAutoNumericElement(priceInput)) {
                                AutoNumeric.getAutoNumericElement(priceInput).set(product.price);
                            }
                            this.calculateRow(index);
                        }
                    }
                });
            },

            addItem() {
                this.items.push({
                    id: Date.now() + Math.random(),
                    item_id_db: null,
                    product_id: '',
                    unit: '', code: '', product_name: '',
                    price: 0, quantity: 1,
                    update_master_price: false,
                    discounts: [],
                    subtotal: 0
                });
            },

            removeItem(index) {
                if(this.items.length > 1) {
                    this.items.splice(index, 1);
                    this.calculateSummary();
                } else {
                    alert('Minimal harus ada 1 barang.');
                }
            },

            calculateRow(index) {
                const item = this.items[index];
                if (!item) return;
                let unitPrice = parseFloat(item.price || 0);
                if (item.discounts && item.discounts.length > 0) {
                    item.discounts.forEach(disc => {
                        let d = parseFloat(disc);
                        if (!isNaN(d) && d > 0) {
                            unitPrice = unitPrice * (1 - (d / 100));
                        }
                    });
                }
                let netUnitPrice = Math.round((unitPrice + Number.EPSILON) * 100) / 100;
                let qty = parseFloat(item.quantity || 0);
                this.items[index].subtotal = Math.round(netUnitPrice * qty);
                this.calculateSummary();
            },

            calculateSummary() {
                const subtotal = this.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
                this.summary.subtotal = Math.round(subtotal);
                let amountAfterDisc = this.summary.subtotal;
                
                if (this.summary.apply_disc_fee) {
                    let rawDiscFee = this.summary.subtotal * (this.summary.disc_fee_percent / 100);
                    this.summary.disc_fee_amount = Math.round(rawDiscFee);
                    amountAfterDisc -= this.summary.disc_fee_amount;
                } else { 
                    this.summary.disc_fee_amount = 0; 
                }

                if (this.summary.apply_rounding) {
                    const roundingVal = parseFloat(this.summary.rounding || 0);
                    amountAfterDisc -= roundingVal;
                }

                amountAfterDisc = Math.round(amountAfterDisc);
                if(amountAfterDisc < 0) amountAfterDisc = 0;

                let dpp = amountAfterDisc;
                if (this.summary.use_custom_dpp) {
                    dpp = amountAfterDisc * this.summary.dpp_factor_value;
                }
                this.summary.dpp = Math.round(dpp);

                const tax = window.taxes.find(t => t.id == this.summary.tax_id);
                let taxRate = tax ? parseFloat(tax.rate) : 0;
                this.summary.ppn = Math.round(this.summary.dpp * (taxRate / 100));

                const shipping = parseFloat(this.summary.shipping || 0);
                this.summary.grand_total = Math.round(amountAfterDisc + this.summary.ppn + shipping);

                this.diff = this.originalGrandTotal - this.summary.grand_total;
            },

            getDiffLabel() {
                if (Math.abs(this.diff) < 1) return 'Tidak Ada Perubahan';
                return this.diff > 0 ? 'Tagihan Berkurang (Overpayment)' : 'Tagihan Bertambah (Underpayment)';
            },

            // Bulk Tools (Copy dari sebelumnya)
            addDiscountLevel(index) { this.items[index].discounts.push(0); },
            removeDiscount(index, dIndex) { this.items[index].discounts.splice(dIndex, 1); this.calculateRow(index); },
            toggleSelectAll(e) { e.target.checked ? this.selectedItems = this.items.map(i => i.id) : this.selectedItems = []; },
            parseBulkString(str) { 
                if (!str) return [];
                return str.toString().split(/[+,]/).map(d => parseFloat(d.trim())).filter(d => !isNaN(d) && d > 0);
            },
            applyBulkDiscount(target) {
                if (!this.bulkDiscountValue) return;
                const newDiscounts = this.parseBulkString(this.bulkDiscountValue);
                this.items.forEach((item, index) => {
                    if (target === 'all' || (target === 'selected' && this.selectedItems.includes(item.id))) {
                        item.discounts = [...newDiscounts]; this.calculateRow(index);
                    }
                });
            },
            toggleDppFactor() {
                if (this.summary.use_custom_dpp) {
                    if (!this.summary.dpp_factor_input) this.summary.dpp_factor_input = '11/12';
                    this.evaluateFraction();
                } else {
                    this.summary.dpp_factor_value = 1;
                    this.calculateSummary();
                }
            },
            evaluateFraction() {
                const input = this.summary.dpp_factor_input;
                try {
                    if (/^[0-9./]+$/.test(input)) {
                        if (input.includes('/')) {
                            const parts = input.split('/');
                            if (parts.length === 2 && parseFloat(parts[1]) !== 0) {
                                this.summary.dpp_factor_value = parseFloat(parts[0]) / parseFloat(parts[1]);
                                this.calculateSummary();
                                return;
                            }
                        } else {
                             this.summary.dpp_factor_value = parseFloat(input);
                             this.calculateSummary();
                             return;
                        }
                    }
                    this.summary.dpp_factor_value = 11/12;
                } catch (e) { this.summary.dpp_factor_value = 1; }
                this.calculateSummary();
            },
            formatMoney(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
            },
            submitForm() {
                const validItems = this.items.filter(i => i.product_id !== '');
                if (validItems.length === 0) { alert('Harap tambahkan minimal 1 produk.'); return; }
                if (Math.abs(this.diff) < 1) { alert('Tidak ada perubahan nilai nominal.'); return; }
                this.isLoading = true;
                document.getElementById('adjustmentForm').submit();
            }
        }));
    });
</script>
@endpush
@endsection
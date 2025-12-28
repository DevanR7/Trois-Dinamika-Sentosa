@extends('admin.layouts.app')

@section('title', 'Buat Retur Pembelian')

@section('content')
<div x-data="purchaseReturnForm()" class="flex flex-col gap-6 pb-20">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="page-title">Buat Retur Baru</h2>
            <p class="text-sm text-slate-500 mt-1">Pilih PO yang sudah selesai (Completed) untuk diretur.</p>
        </div>
        <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-secondary">
            Batal
        </a>
    </div>

    {{-- Error Alert --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-lg text-sm animate-enter">
            <p class="font-bold mb-1">Gagal menyimpan retur:</p>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.purchase-returns.store') }}" method="POST" id="return-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: INFO DASAR --}}
            <div class="space-y-6">
                
                <div class="card p-5">
                    <h3 class="font-bold text-slate-700 dark:text-white mb-4 border-b pb-2 border-slate-100 dark:border-slate-700">Informasi Retur</h3>
                    
                    <div class="space-y-4">
                        {{-- Pilih PO --}}
                        <div>
                            <label class="form-label">Pilih PO Asal <span class="text-red-500">*</span></label>
                            <div wire:ignore>
                                <select name="purchase_order_id" x-model="poId" x-init="initPoSelect($el)" class="tom-select w-full" placeholder="Cari No. PO..." required>
                                    <option value="">- Pilih PO -</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->po_id }}">
                                            {{ $po->po_number }} - {{ $po->supplier->supplier_name }} ({{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Hanya PO berstatus 'Completed' yang muncul.</p>
                        </div>

                        {{-- Tanggal Retur --}}
                        <div>
                            <label class="form-label">Tanggal Retur <span class="text-red-500">*</span></label>
                            <input type="date" name="return_date" value="{{ date('Y-m-d') }}" class="form-input w-full" required>
                        </div>

                        {{-- Jenis Penanganan --}}
                        <div>
                            <label class="form-label">Jenis Penanganan <span class="text-red-500">*</span></label>
                            <div class="flex flex-col gap-3 mt-1">
                                <label class="relative flex items-start p-3 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="radio" name="return_handling_type" value="deduct_invoice" class="mt-1 form-radio text-indigo-600" checked>
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Potong Tagihan (Hutang)</span>
                                        <span class="block text-xs text-slate-500">Mengurangi sisa hutang pada PO ini.</span>
                                    </div>
                                </label>
                                <label class="relative flex items-start p-3 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="radio" name="return_handling_type" value="store_as_deposit" class="mt-1 form-radio text-indigo-600">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Simpan sbg Deposit Supplier</span>
                                        <span class="block text-xs text-slate-500">Nilai retur disimpan sebagai deposit (Debit) untuk pembelian selanjutnya.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div>
                            <label class="form-label">Catatan / Alasan</label>
                            <textarea name="notes" rows="3" class="form-input w-full" placeholder="Contoh: Barang rusak, kualitas buruk..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: ITEM BARANG --}}
            <div class="lg:col-span-2">
                <div class="card min-h-[500px] flex flex-col">
                    <div class="card-header bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="card-header-title">Pilih Barang yang Diretur</h3>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="isLoading" class="flex-1 flex flex-col justify-center items-center py-20 text-slate-400">
                        <svg class="animate-spin h-8 w-8 text-indigo-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">Memuat item PO...</span>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!isLoading && items.length === 0" class="flex-1 flex flex-col justify-center items-center py-20 text-slate-400">
                        <i class="material-icons text-5xl mb-2 text-slate-200">shopping_cart_checkout</i>
                        <p>Silakan pilih Nomor PO terlebih dahulu.</p>
                    </div>

                    {{-- Table Items --}}
                    <div x-show="!isLoading && items.length > 0" class="flex-1 overflow-x-auto">
                        <table class="table-modern w-full">
                            <thead class="bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th class="w-[35%]">Produk</th>
                                    <th class="w-[15%] text-right">Harga Beli</th>
                                    <th class="w-[10%] text-center">Beli</th>
                                    <th class="w-[10%] text-center">Sdh Retur</th>
                                    <th class="w-[15%] text-center bg-indigo-50/50">Qty Retur</th>
                                    <th class="w-[15%] text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, index) in items" :key="item.item_id">
                                    <tr :class="{'bg-rose-50/30': item.return_qty > 0}">
                                        {{-- Hidden Inputs --}}
                                        <input type="hidden" :name="`items[${index}][item_id]`" :value="item.item_id">
                                        
                                        <td class="align-top py-3">
                                            <div class="font-bold text-slate-700 dark:text-white" x-text="item.product.product_name"></div>
                                            <div class="text-xs text-slate-500" x-text="item.product.product_code"></div>
                                        </td>
                                        
                                        <td class="align-top py-3 text-right text-sm">
                                            Rp <span x-text="formatRupiah(item.price_per_unit)"></span>
                                        </td>
                                        
                                        <td class="align-top py-3 text-center text-sm">
                                            <span x-text="formatNumber(item.quantity)"></span>
                                        </td>
                                        
                                        <td class="align-top py-3 text-center text-sm text-slate-500">
                                            <span x-text="formatNumber(item.quantity_returned)"></span>
                                        </td>
                                        
                                        {{-- Input Qty Retur (Dengan Validasi) --}}
                                        <td class="align-top py-2 px-2 bg-indigo-50/30">
                                            <div class="relative">
                                                <input type="number" 
                                                       :name="`items[${index}][quantity]`"
                                                       x-model.number="item.return_qty"
                                                       @input="validateQty(index)"
                                                       class="form-input text-center font-bold text-rose-600 w-full h-9 text-sm focus:ring-rose-500 focus:border-rose-500"
                                                       placeholder="0"
                                                       min="0"
                                                       step="0.01">
                                            </div>
                                            <div class="text-[10px] text-center mt-1 text-slate-400">
                                                Max: <span x-text="formatNumber(item.quantity - item.quantity_returned)"></span>
                                            </div>
                                        </td>
                                        
                                        <td class="align-top py-3 text-right font-bold text-slate-700 dark:text-white">
                                            Rp <span x-text="formatRupiah(item.return_qty * item.price_per_unit)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer Total --}}
                    <div x-show="items.length > 0" class="p-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 flex justify-between items-center">
                        <div class="text-sm text-slate-500">
                            Total Item Diretur: <strong class="text-slate-800 dark:text-white" x-text="totalItemsReturned"></strong>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-xs text-slate-500 uppercase font-bold tracking-wider">Total Nilai Retur</span>
                            <span class="text-2xl font-black text-rose-600">Rp <span x-text="formatRupiah(grandTotal)"></span></span>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-100 flex justify-end gap-3">
                        <button type="submit" class="btn btn-primary btn-lg shadow-lg shadow-indigo-200 dark:shadow-none" :disabled="grandTotal <= 0">
                            <i class="material-icons mr-2">save</i> Simpan Retur
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('purchaseReturnForm', () => ({
            poId: '',
            items: [],
            isLoading: false,

            initPoSelect(el) {
                new TomSelect(el, {
                    ...window.defaultTomSelectConfig,
                    onChange: (value) => {
                        this.poId = value;
                        this.fetchItems();
                    }
                });
            },

            async fetchItems() {
                if (!this.poId) {
                    this.items = [];
                    return;
                }

                this.isLoading = true;
                try {
                    const response = await fetch(`/admin/api/purchase-orders/${this.poId}/items`);
                    const data = await response.json();
                    
                    this.items = data.items.map(item => ({
                        ...item,
                        return_qty: 0,
                        quantity: parseFloat(item.quantity),
                        quantity_returned: parseFloat(item.quantity_returned),
                        price_per_unit: parseFloat(item.price_per_unit)
                    }));
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Gagal memuat item PO.', 'error');
                    this.items = [];
                } finally {
                    this.isLoading = false;
                }
            },

            // --- VALIDASI QTY AGAR TIDAK LEBIH DARI STOK BELI ---
            validateQty(index) {
                const item = this.items[index];
                const max = item.quantity - item.quantity_returned;

                if (item.return_qty > max) {
                    showToast('Jumlah retur melebihi jumlah yang dibeli (' + this.formatNumber(max) + ')', 'error');
                    this.$nextTick(() => {
                        this.items[index].return_qty = max; // Reset ke max
                    });
                }
                
                if (item.return_qty < 0) {
                    this.items[index].return_qty = 0;
                }
            },

            get totalItemsReturned() {
                return this.items.filter(i => i.return_qty > 0).length;
            },

            get grandTotal() {
                return this.items.reduce((sum, item) => {
                    return sum + (item.return_qty * item.price_per_unit);
                }, 0);
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(value || 0);
            },

            formatNumber(value) {
                return parseFloat(value).toLocaleString('id-ID');
            }
        }));
    });
</script>
@endpush
@endsection
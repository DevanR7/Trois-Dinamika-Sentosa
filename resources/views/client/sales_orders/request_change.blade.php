@extends('client.layouts.app')

@section('title', 'Ajukan Perubahan Pesanan #' . $order->order_number)

@section('content')

    <div class="max-w-4xl mx-auto">
        
        {{-- Header Info --}}
        <div class="mb-6">
            <a href="{{ route('client.sales-orders.show', $order->order_id) }}" class="text-slate-500 hover:text-indigo-600 text-sm font-medium flex items-center gap-1 mb-2">
                <i class="material-icons text-sm">arrow_back</i> Kembali ke Detail
            </a>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Form Pengajuan Perubahan</h1>
            <p class="text-slate-500 text-sm mt-1">Anda dapat mengajukan pembatalan atau revisi item pada pesanan ini.</p>
        </div>

        {{-- ✅ PERBAIKAN 1: TAMPILKAN ALASAN PENOLAKAN JIKA ADA --}}
        {{-- Cek apakah ada request sebelumnya yang ditolak atau order status rejected --}}
        @if($order->status === 'rejected' && $order->notes)
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                <i class="material-icons text-red-500 mt-0.5">error_outline</i>
                <div>
                    <h4 class="text-sm font-bold text-red-800 uppercase mb-1">Alasan Penolakan Sebelumnya</h4>
                    <p class="text-sm text-red-700 italic">"{{ $order->notes }}"</p>
                </div>
            </div>
        @endif

        {{-- Cek request terakhir yang ditolak (jika ada) --}}
        @php
            $lastRejectedRequest = $order->changeRequests()
                ->where('status', 'rejected')
                ->latest('created_at')
                ->first();
        @endphp

        @if($lastRejectedRequest && $lastRejectedRequest->admin_notes)
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-3">
                <i class="material-icons text-amber-500 mt-0.5">warning</i>
                <div>
                    <h4 class="text-sm font-bold text-amber-800 uppercase mb-1">Catatan Penolakan Revisi Terakhir</h4>
                    <p class="text-sm text-amber-700 italic">"{{ $lastRejectedRequest->admin_notes }}"</p>
                </div>
            </div>
        @endif


        {{-- Main Form Container --}}
        <div class="card" x-data="changeRequestForm()">
            <form action="{{ route('client.sales-orders.requestChange.store', $order->order_id) }}" method="POST">
                @csrf
                
                <div class="card-body space-y-6">

                    {{-- 1. Pilihan Tipe Request --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-3">Jenis Permintaan <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            {{-- Option: Cancel --}}
                            <label class="cursor-pointer group">
                                <input type="radio" name="request_type" value="cancel" x-model="requestType" class="peer sr-only">
                                <div class="p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <i class="material-icons">cancel</i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-800 dark:text-white">Batalkan Pesanan</h4>
                                            <p class="text-xs text-slate-500">Batalkan seluruh pesanan ini.</p>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            {{-- Option: Modify --}}
                            <label class="cursor-pointer group">
                                <input type="radio" name="request_type" value="modify" x-model="requestType" class="peer sr-only">
                                <div class="p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <i class="material-icons">edit_note</i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-800 dark:text-white">Revisi Item</h4>
                                            <p class="text-xs text-slate-500">Ubah jumlah, hapus item, atau tambah item baru.</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <hr class="border-slate-100 dark:border-slate-700">

                    {{-- 2. Alasan / Catatan --}}
                    <div>
                        <label for="client_notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Alasan / Catatan Tambahan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="client_notes" id="client_notes" rows="3" required
                            class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-slate-800 dark:border-slate-600 dark:text-white"
                            placeholder="Contoh: Salah jumlah pesanan, ingin ganti produk, dsb..."></textarea>
                    </div>

                    {{-- 3. Dynamic Items Table (Hanya muncul jika Modify) --}}
                    <template x-if="requestType === 'modify'">
                        <div class="space-y-4 animate-enter">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-slate-800 dark:text-white">Daftar Item Revisi</h3>
                                <button type="button" @click="addNewItem()" class="btn btn-primary btn-sm">
                                    <i class="material-icons text-sm">add</i> Tambah Item
                                </button>
                            </div>

                            <div class="table-container bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-1 overflow-visible">
                                <table class="table-modern w-full">
                                    <thead>
                                        <tr>
                                            <th class="w-5/12">Produk</th>
                                            <th class="w-2/12 text-center">Qty Lama</th>
                                            <th class="w-3/12 text-center">Qty Baru</th>
                                            <th class="w-2/12 text-center">Status</th>
                                            <th class="w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, index) in items" :key="item.ui_id">
                                            <tr :class="{'bg-red-50 dark:bg-red-900/10': item.action === 'remove', 'bg-blue-50 dark:bg-blue-900/10': item.action === 'add', 'bg-yellow-50 dark:bg-yellow-900/10': item.action === 'update_qty'}">
                                                
                                                {{-- Hidden Inputs (Ini yang dikirim ke backend) --}}
                                                <td class="p-3 align-top">
                                                    {{-- ✅ PERBAIKAN 2: Pastikan input hidden ter-render dengan benar untuk item yang dihapus --}}
                                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                                                    <input type="hidden" :name="`items[${index}][original_quantity]`" :value="item.original_quantity">
                                                    <input type="hidden" :name="`items[${index}][action]`" :value="item.action">
                                                    
                                                    {{-- ✅ PENTING: Kirim quantity 0 jika remove, agar lolos validasi 'numeric' backend --}}
                                                    <input type="hidden" :name="`items[${index}][quantity]`" :value="item.action === 'remove' ? 0 : item.quantity">

                                                    {{-- Tampilan Item Lama --}}
                                                    <div x-show="item.type === 'existing'">
                                                        <span class="font-medium text-slate-700 dark:text-slate-200 block" x-text="item.product_name"></span>
                                                        <span class="text-[10px] text-slate-500 block" x-text="item.product_code"></span>
                                                    </div>

                                                    {{-- Tampilan Item Baru (Tom Select) --}}
                                                    <div x-show="item.type === 'new'" class="min-w-[200px]">
                                                        <select class="tom-select-init w-full"
                                                            x-init="initTomSelect($el, item.ui_id)">
                                                            <option value="">Pilih Produk...</option>
                                                            @foreach($products as $product)
                                                                <option value="{{ $product->product_id }}">{{ $product->product_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div x-show="!item.product_id" class="text-[10px] text-red-500 mt-1">* Wajib dipilih</div>
                                                    </div>
                                                </td>

                                                {{-- Qty Lama --}}
                                                <td class="text-center text-slate-500 p-3 align-top">
                                                    <span x-text="item.original_quantity ? parseFloat(item.original_quantity) : '-'"></span>
                                                </td>

                                                {{-- Qty Baru --}}
                                                <td class="p-3 align-top">
                                                    <div class="flex justify-center">
                                                        {{-- Input Visible untuk User --}}
                                                        <input type="number" 
                                                            step="any"
                                                            class="form-input text-center w-24 h-9 text-sm font-bold"
                                                            x-model.number="item.quantity"
                                                            @input="calculateAction(item)"
                                                            :disabled="item.action === 'remove'"
                                                            min="0">
                                                    </div>
                                                </td>

                                                {{-- Status Badge --}}
                                                <td class="text-center p-3 align-top">
                                                    <span class="badge badge-pill text-[10px] uppercase inline-block mt-1"
                                                        :class="{
                                                            'badge-danger': item.action === 'remove',
                                                            'badge-primary': item.action === 'add',
                                                            'badge-warning': item.action === 'update_qty',
                                                            'bg-slate-100 text-slate-500': !item.action
                                                        }"
                                                        x-text="getActionLabel(item.action)">
                                                    </span>
                                                </td>

                                                {{-- Action Buttons --}}
                                                <td class="text-center p-3 align-top">
                                                    {{-- Hapus Item (Tandai Remove untuk Existing) --}}
                                                    <button type="button" x-show="item.type === 'existing' && item.action !== 'remove'" 
                                                            @click="markAsRemoved(item)" 
                                                            class="text-slate-400 hover:text-red-500 transition-colors p-1" title="Hapus Item">
                                                        <i class="material-icons text-lg">delete</i>
                                                    </button>

                                                    {{-- Undo Hapus --}}
                                                    <button type="button" x-show="item.type === 'existing' && item.action === 'remove'" 
                                                            @click="restoreItem(item)" 
                                                            class="text-red-500 hover:text-emerald-500 transition-colors p-1" title="Batalkan Hapus">
                                                        <i class="material-icons text-lg">undo</i>
                                                    </button>

                                                    {{-- Hapus Baris (Untuk New Item) --}}
                                                    <button type="button" x-show="item.type === 'new'" 
                                                            @click="removeNewItem(index)" 
                                                            class="text-slate-400 hover:text-red-500 transition-colors p-1" title="Hapus Baris">
                                                        <i class="material-icons text-lg">close</i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-slate-500 italic mt-2">
                                * Ubah jumlah quantity menjadi 0 atau klik ikon sampah untuk menghapus item dari pesanan.
                            </p>
                        </div>
                    </template>

                </div>

                <div class="card-footer flex flex-col sm:flex-row justify-end gap-3 bg-slate-50 dark:bg-slate-800/50">
                    <a href="{{ route('client.sales-orders.show', $order->order_id) }}" class="btn btn-secondary order-2 sm:order-1">Batal</a>
                    
                    <button type="submit" class="btn btn-primary order-1 sm:order-2"
                        :disabled="!isFormValid">
                        <i class="material-icons text-sm">send</i> 
                        <span x-text="requestType === 'cancel' ? 'Kirim Pembatalan' : 'Kirim Revisi'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('changeRequestForm', () => ({
                requestType: 'modify', 
                items: [],

                get isFormValid() {
                    if (this.requestType === 'cancel') return true; 

                    if (this.items.length === 0) return false;

                    // Cek apakah item baru sudah dipilih produknya
                    const hasEmptyProduct = this.items.some(i => i.type === 'new' && !i.product_id);
                    if (hasEmptyProduct) return false;
                    
                    // Cek apakah setidaknya ada SATU perubahan
                    const hasChanges = this.items.some(i => i.action !== null);
                    if (!hasChanges) return false;

                    return true;
                },

                init() {
                    // Load existing items dari PHP
                    const existingItems = @json($order->items);
                    
                    this.items = existingItems.map((item) => ({
                        ui_id: 'exist_' + item.item_id, 
                        type: 'existing',
                        product_id: item.product_id,
                        product_name: item.product.product_name,
                        product_code: item.product.product_code,
                        original_quantity: parseFloat(item.quantity),
                        quantity: parseFloat(item.quantity),
                        action: null 
                    }));
                },

                addNewItem() {
                    this.items.push({
                        ui_id: 'new_' + Date.now() + Math.random(),
                        type: 'new',
                        product_id: '',
                        product_name: '',
                        product_code: '',
                        original_quantity: null,
                        quantity: 1,
                        action: 'add'
                    });
                },

                removeNewItem(index) {
                    this.items.splice(index, 1);
                },

                markAsRemoved(item) {
                    item.saved_qty = item.quantity;
                    item.quantity = 0; // Visual jadi 0
                    item.action = 'remove';
                },

                restoreItem(item) {
                    // Kembalikan ke qty awal jika di-undo
                    item.quantity = item.saved_qty || item.original_quantity;
                    item.action = null; 
                    
                    // Cek ulang jika qty masih beda dari original (kasus user ubah qty dulu baru hapus)
                    this.calculateAction(item);
                },

                calculateAction(item) {
                    if (item.type === 'existing') {
                        let newQty = parseFloat(item.quantity);
                        
                        if (isNaN(newQty) || newQty <= 0) {
                            item.action = 'remove';
                        } else if (newQty !== item.original_quantity) {
                            item.action = 'update_qty';
                        } else {
                            item.action = null; // Tidak ada perubahan
                        }
                    }
                },

                getActionLabel(action) {
                    if (action === 'add') return 'Tambah';
                    if (action === 'remove') return 'Hapus';
                    if (action === 'update_qty') return 'Revisi Qty';
                    return 'Tetap';
                },

                initTomSelect(el, uiId) {
                    if (el.tomselect) return;

                    new TomSelect(el, {
                        ...window.defaultTomSelectConfig,
                        dropdownParent: 'body',
                        onChange: (value) => {
                            const item = this.items.find(i => i.ui_id === uiId);
                            if (item) {
                                item.product_id = value;
                            }
                        }
                    });
                }
            }));
        });
    </script>
    @endpush

@endsection
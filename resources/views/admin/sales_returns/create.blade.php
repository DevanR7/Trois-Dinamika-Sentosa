@extends('admin.layouts.app')

@section('title', 'Buat Retur Penjualan')

@section('content')
<div class="flex flex-col gap-6" x-data="salesReturnForm()">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.sales-returns.index') }}" class="btn-icon btn-secondary" title="Kembali">
                    <i class="material-icons text-lg">arrow_back</i>
                </a>
                <div>
                    <h1 class="page-title text-xl font-bold tracking-tight">Buat Retur Baru</h1>
                    <p class="text-sm text-slate-500 mt-1">Pilih invoice penjualan untuk memproses pengembalian barang.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN FORM --}}
    <form action="{{ route('admin.sales-returns.store') }}" method="POST" @submit.prevent="submitForm">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: Informasi Utama --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Kartu Pilih Invoice --}}
                <div class="card p-5">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-white uppercase mb-4 flex items-center gap-2">
                        <i class="material-icons text-indigo-500">receipt_long</i> Sumber Invoice
                    </h2>

                    <div class="space-y-4">
                        {{-- Dropdown Invoice --}}
                        <div>
                            <label class="form-label label-required">Pilih Invoice</label>
                            <select name="sales_invoice_id" 
                                    id="invoice_select"
                                    class="tom-select w-full" 
                                    x-model="invoiceId"
                                    data-placeholder="Cari No. Invoice..."
                                    required>
                                <option value=""></option>
                                @foreach($invoices as $inv)
                                    <option value="{{ $inv->invoice_id }}">
                                        {{ $inv->invoice_number }} - {{ $inv->client->client_name ?? 'Umum' }} ({{ $inv->order_date->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1">* Hanya invoice status unpaid/paid/partial yang muncul.</p>
                        </div>

                        {{-- Loading State --}}
                        <div x-show="isLoading" class="flex items-center justify-center p-4 bg-slate-50 rounded-lg border border-slate-100">
                            <svg class="animate-spin h-5 w-5 text-indigo-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs text-slate-500">Memuat data item...</span>
                        </div>

                        {{-- Info Klien (Read Only) --}}
                        <div x-show="invoiceDetails" x-transition.opacity class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-lg">
                            <div class="text-xs font-bold text-indigo-600 mb-1">KLIEN</div>
                            <div class="text-sm font-bold text-slate-700" x-text="invoiceDetails?.client_name || '-'"></div>
                            
                            <div class="border-t border-indigo-200 my-2"></div>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-xs font-bold text-indigo-600">TANGGAL ORDER</div>
                                    <div class="text-xs text-slate-600" x-text="invoiceDetails?.order_date || '-'"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-bold text-indigo-600">TOTAL INVOICE</div>
                                    <div class="text-xs text-slate-600 font-mono" x-text="formatRupiah(invoiceDetails?.total_amount || 0)"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kartu Detail Retur --}}
                <div class="card p-5">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-white uppercase mb-4 flex items-center gap-2">
                        <i class="material-icons text-indigo-500">settings</i> Detail Retur
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label label-required">Tanggal Retur</label>
                            <input type="date" name="return_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div>
                            <label class="form-label label-required">Metode Penanganan</label>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="relative flex items-start p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center h-5">
                                        <input type="radio" name="return_handling_type" value="deduct_invoice" class="form-check-input" checked>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="block font-bold text-slate-700">Potong Tagihan</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Mengurangi sisa hutang pada invoice ini. Stok barang bertambah.</span>
                                    </div>
                                </label>

                                <label class="relative flex items-start p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center h-5">
                                        <input type="radio" name="return_handling_type" value="store_as_credit" class="form-check-input">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="block font-bold text-slate-700">Simpan sebagai Deposit</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Nilai retur masuk ke saldo deposit klien. Cocok jika invoice sudah lunas.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-textarea h-24" placeholder="Alasan pengembalian barang..."></textarea>
                        </div>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: Tabel Item --}}
            <div class="lg:col-span-2">
                <div class="card min-h-[500px] flex flex-col">
                    <div class="card-header">
                        <h2 class="card-header-title">Item yang Dikembalikan</h2>
                        <div x-show="items.length > 0" class="text-xs font-medium text-slate-500">
                            <span x-text="items.length"></span> produk tersedia
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div x-show="!invoiceId" class="flex-1 flex flex-col items-center justify-center p-12 text-center text-slate-400">
                        <i class="material-icons text-6xl mb-4 text-slate-200">shopping_cart_checkout</i>
                        <p>Silakan pilih invoice di sebelah kiri<br>untuk memuat daftar item.</p>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="isLoading" class="flex-1 flex flex-col items-center justify-center p-12" style="display: none;">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mb-3"></div>
                        <p class="text-sm text-slate-500">Sedang mengambil data invoice...</p>
                    </div>

                    {{-- Table Items --}}
                    <div x-show="invoiceId && !isLoading" class="table-container border-0 shadow-none rounded-none" style="display: none;">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th class="w-10">
                                        <input type="checkbox" class="form-check-input" @change="toggleAll($event.target.checked)">
                                    </th>
                                    <th>Produk</th>
                                    <th class="text-right">Harga Satuan</th>
                                    <th class="text-center">Jml Beli</th>
                                    <th class="text-center">Sdh Retur</th>
                                    <th class="w-32 text-center">Jml Retur</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="item.item_id">
                                    <tr :class="{'bg-rose-50/50': item.return_qty > 0}">
                                        <td>
                                            <input type="checkbox" class="form-check-input" 
                                                   :checked="item.return_qty > 0"
                                                   @change="item.return_qty = $event.target.checked ? 1 : 0">
                                            <input type="hidden" :name="`items[${index}][item_id]`" :value="item.item_id">
                                        </td>
                                        <td>
                                            <div class="font-bold text-slate-700" x-text="item.product?.product_name"></div>
                                            <div class="text-xs text-slate-500" x-text="item.product?.product_code"></div>
                                        </td>
                                        <td class="text-right font-mono text-xs">
                                            <span x-text="formatRupiah(item.price_per_unit)"></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-primary" x-text="formatNumber(item.quantity)"></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge" :class="item.quantity_returned > 0 ? 'badge-warning' : 'badge-primary'" x-text="formatNumber(item.quantity_returned)"></span>
                                        </td>
                                        <td>
                                            <div class="flex items-center justify-center">
                                                <input type="number" 
                                                       :name="`items[${index}][quantity]`"
                                                       x-model.number="item.return_qty" 
                                                       class="form-input h-9 text-center font-bold text-rose-600 w-24"
                                                       min="0" 
                                                       :max="item.quantity - item.quantity_returned"
                                                       step="1">
                                            </div>
                                            <div x-show="item.return_qty > (item.quantity - item.quantity_returned)" class="text-[10px] text-rose-500 text-center mt-1">
                                                Max: <span x-text="formatNumber(item.quantity - item.quantity_returned)"></span>
                                            </div>
                                        </td>
                                        <td class="text-right font-bold font-mono text-rose-600">
                                            <span x-text="formatRupiah(item.return_qty * item.price_per_unit)"></span>
                                        </td>
                                    </tr>
                                </template>
                                
                                {{-- Jika item kosong setelah difetch --}}
                                <template x-if="items.length === 0 && !isLoading">
                                    <tr>
                                        <td colspan="7" class="text-center py-8 text-slate-400 italic">
                                            Tidak ada item pada invoice ini.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer: Total Summary --}}
                    <div x-show="invoiceId" class="mt-auto p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-xs text-slate-500">
                            * Item yang diisi 0 tidak akan diproses.<br>
                            * Harga satuan mengacu pada harga di invoice (termasuk diskon item jika ada).
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-right">
                                <div class="text-xs text-slate-500 font-bold uppercase">Total Pengembalian</div>
                                <div class="text-2xl font-bold text-rose-600 font-mono" x-text="formatRupiah(grandTotal)"></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg shadow-lg shadow-indigo-500/20" :disabled="grandTotal <= 0">
                                <i class="material-icons mr-2">save</i> Simpan Retur
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('salesReturnForm', () => ({
            invoiceId: '',
            isLoading: false,
            invoiceDetails: null,
            items: [],

            init() {
                // Integrasi dengan Tom Select
                const selectEl = document.getElementById('invoice_select');
                if (selectEl) {
                    // Tom Select sudah di-init oleh app.js global, 
                    // kita hanya perlu listen event change standard.
                    selectEl.addEventListener('change', (e) => {
                        this.invoiceId = e.target.value;
                        this.fetchInvoiceItems();
                    });
                }
            },

            async fetchInvoiceItems() {
                if (!this.invoiceId) {
                    this.items = [];
                    this.invoiceDetails = null;
                    return;
                }

                this.isLoading = true;
                this.items = []; // Reset dulu

                try {
                    // Endpoint: /admin/api/invoices/{id}/items
                    const response = await fetch(`/admin/api/invoices/${this.invoiceId}/items`);
                    if (!response.ok) throw new Error('Gagal mengambil data');
                    
                    const data = await response.json();
                    
                    // Set Info Klien
                    this.invoiceDetails = {
                        client_name: data.invoice.client?.client_name,
                        order_date: new Date(data.invoice.order_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
                        total_amount: data.invoice.total_amount
                    };

                    // Map items dengan properti tambahan untuk input
                    this.items = data.items.map(item => ({
                        ...item,
                        return_qty: 0, // Default 0
                        // Pastikan quantity dan returned numerik
                        quantity: parseFloat(item.quantity),
                        quantity_returned: parseFloat(item.quantity_returned || 0),
                        price_per_unit: parseFloat(item.price_per_unit)
                    }));

                } catch (error) {
                    console.error(error);
                    showToast('Gagal memuat data invoice. Silakan coba lagi.', 'error');
                } finally {
                    this.isLoading = false;
                }
            },

            toggleAll(checked) {
                this.items.forEach(item => {
                    const maxReturn = item.quantity - item.quantity_returned;
                    // Jika dicentang, set max, jika tidak 0
                    item.return_qty = checked ? (maxReturn > 0 ? maxReturn : 0) : 0;
                });
            },

            get grandTotal() {
                return this.items.reduce((sum, item) => {
                    return sum + (item.return_qty * item.price_per_unit);
                }, 0);
            },

            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(number);
            },

            formatNumber(number) {
                return new Intl.NumberFormat('id-ID').format(number);
            },

            submitForm(e) {
                if (this.grandTotal <= 0) {
                    showToast('Harap isi jumlah retur minimal pada satu item.', 'warning');
                    return;
                }
                
                // Validasi Client Side ekstra
                const invalidItem = this.items.find(item => item.return_qty > (item.quantity - item.quantity_returned));
                if (invalidItem) {
                    showToast(`Jumlah retur untuk ${invalidItem.product.product_name} melebihi batas.`, 'error');
                    return;
                }

                // Jika lolos, submit manual
                e.target.submit();
            }
        }));
    });
</script>
@endpush
@endsection
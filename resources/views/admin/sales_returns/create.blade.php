@extends('admin.layouts.app')

@section('title', 'Buat Retur Penjualan')

@section('content')
    <form action="{{ route('admin.sales-returns.store') }}" method="POST" id="create-return-form">
        @csrf
        <div class="flex flex-col gap-6">
            
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="page-title">Buat Retur Baru</h2>
                    <a href="{{ route('admin.sales-returns.index') }}" class="flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition-colors mt-1">
                        <i class="material-icons text-base">arrow_back</i> Kembali ke Daftar
                    </a>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary" id="btn-save">
                        <i class="material-icons text-lg">save</i>
                        Simpan Retur
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Left Column: Form Info --}}
                <div class="lg:col-span-1 flex flex-col gap-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-header-title">Informasi Retur</h3>
                        </div>
                        <div class="card-body flex flex-col gap-4">
                            
                            {{-- Pilih Invoice --}}
                            <div>
                                <label class="form-label">Pilih Invoice <span class="text-red-500">*</span></label>
                                <select name="sales_invoice_id" id="sales_invoice_id" class="tom-select" required>
                                    <option value="">Cari No. Invoice...</option>
                                    @foreach($invoices as $inv)
                                        <option value="{{ $inv->invoice_id }}" 
                                                {{ old('sales_invoice_id') == $inv->invoice_id ? 'selected' : '' }}>
                                            {{ $inv->invoice_number }} - {{ $inv->client->client_name }} 
                                            ({{ \Carbon\Carbon::parse($inv->order_date)->format('d/m/Y') }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-slate-400 mt-1">Hanya menampilkan invoice aktif (bukan draft/batal).</p>
                            </div>

                            {{-- Tanggal Retur --}}
                            <div>
                                <label class="form-label">Tanggal Retur <span class="text-red-500">*</span></label>
                                <input type="date" name="return_date" 
                                       value="{{ old('return_date', date('Y-m-d')) }}" 
                                       class="form-input" required>
                            </div>

                            {{-- Tipe Penanganan --}}
                            <div>
                                <label class="form-label">Tindakan <span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-1 gap-2">
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 transition-colors">
                                        <input type="radio" name="return_handling_type" value="deduct_invoice" 
                                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500" 
                                               {{ old('return_handling_type', 'deduct_invoice') == 'deduct_invoice' ? 'checked' : '' }}>
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Potong Tagihan</span>
                                            <span class="block text-xs text-slate-500">Mengurangi sisa hutang invoice.</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 transition-colors">
                                        <input type="radio" name="return_handling_type" value="store_as_credit" 
                                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                               {{ old('return_handling_type') == 'store_as_credit' ? 'checked' : '' }}>
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-slate-700 dark:text-slate-200">Simpan sebagai Deposit</span>
                                            <span class="block text-xs text-slate-500">Masuk ke saldo kredit klien.</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Catatan --}}
                            <div>
                                <label class="form-label">Catatan / Alasan</label>
                                <textarea name="notes" rows="3" class="form-input" placeholder="Contoh: Barang rusak, salah kirim, dll...">{{ old('notes') }}</textarea>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Right Column: Items Table --}}
                <div class="lg:col-span-2">
                    <div class="card h-full flex flex-col">
                        <div class="card-header flex justify-between items-center">
                            <h3 class="card-header-title">Item Barang</h3>
                            <div id="loading-items" class="hidden text-sm text-indigo-600 flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Memuat item...
                            </div>
                        </div>
                        
                        <div class="card-body p-0 flex-1 overflow-hidden flex flex-col">
                            {{-- Placeholder jika belum pilih invoice --}}
                            <div id="empty-state" class="flex flex-col items-center justify-center py-12 text-slate-400">
                                <i class="material-icons text-5xl mb-3">playlist_add</i>
                                <p class="text-sm">Silakan pilih Invoice terlebih dahulu.</p>
                            </div>

                            {{-- Tabel Item --}}
                            <div id="items-container" class="hidden w-full overflow-x-auto">
                                <table class="table-modern w-full">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-right">Harga Satuan</th>
                                            <th class="text-center w-24">Qty Terjual</th>
                                            <th class="text-center w-24">Sdh Retur</th>
                                            <th class="text-center w-32">Qty Retur</th>
                                            <th class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-table-body">
                                        {{-- Rows populated via JS --}}
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-50 dark:bg-slate-800 font-bold border-t border-slate-200 dark:border-slate-700">
                                            <td colspan="5" class="text-right py-4 text-slate-600 dark:text-slate-300 uppercase text-xs tracking-wider">
                                                Total Nilai Retur
                                            </td>
                                            <td class="text-right py-4 px-6 text-indigo-600 dark:text-indigo-400 text-lg">
                                                <span id="grand-total-display">0</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const invoiceSelect = document.getElementById('sales_invoice_id');
            const itemsContainer = document.getElementById('items-container');
            const emptyState = document.getElementById('empty-state');
            const tableBody = document.getElementById('items-table-body');
            const loadingIndicator = document.getElementById('loading-items');
            const grandTotalDisplay = document.getElementById('grand-total-display');

            // Tom Select Instance (karena class .tom-select di init di app.js)
            // Kita perlu akses instance untuk event listener
            // Namun karena app.js global init, kita pakai event 'change' standar pada elemen asli
            // Tom Select akan memicu event change pada elemen select asli.

            invoiceSelect.addEventListener('change', function() {
                const invoiceId = this.value;
                
                if (!invoiceId) {
                    resetTable();
                    return;
                }

                fetchInvoiceItems(invoiceId);
            });

            function resetTable() {
                itemsContainer.classList.add('hidden');
                emptyState.classList.remove('hidden');
                tableBody.innerHTML = '';
                updateGrandTotal();
            }

            async function fetchInvoiceItems(invoiceId) {
                // UI States
                emptyState.classList.add('hidden');
                itemsContainer.classList.remove('hidden');
                loadingIndicator.classList.remove('hidden');
                tableBody.innerHTML = ''; // Clear old

                try {
                    const response = await fetch(`/admin/api/invoices/${invoiceId}/items`);
                    if (!response.ok) throw new Error('Gagal mengambil data item.');
                    
                    const data = await response.json();
                    const items = data.items;
                    
                    // Invoice level discount (percentage) can be accessed via data.invoice.discount_percentage
                    const invoiceDiscountPercent = parseFloat(data.invoice.discount_percentage) || 0;

                    if (items.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-slate-500 italic">Tidak ada item dalam invoice ini.</td></tr>`;
                    } else {
                        items.forEach((item, index) => {
                            // Hitung max qty yang bisa diretur (Qty Awal - Qty Sudah Diretur)
                            const maxQty = parseFloat(item.quantity) - parseFloat(item.quantity_returned);
                            
                            // Hitung harga satuan bersih setelah diskon invoice (pro-rate logic)
                            // Jika diskon per item ada (tidak ada di skema invoice_items standar Anda, tapi ada discount_percentage global)
                            // Logic Controller: $priceAfterDiscount = $originalItem->price_per_unit * (1 - $discountRate);
                            const rawPrice = parseFloat(item.price_per_unit);
                            const netPrice = rawPrice * (1 - (invoiceDiscountPercent / 100));

                            // Skip jika sudah diretur semua
                            if (maxQty <= 0) return;

                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <div class="font-medium text-slate-700 dark:text-slate-200 text-sm">
                                        ${item.product.product_name}
                                    </div>
                                    <div class="text-xs text-slate-500">${item.product.product_code}</div>
                                    <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                                </td>
                                <td class="text-right">
                                    <div class="text-sm font-medium">Rp ${new Intl.NumberFormat('id-ID').format(netPrice)}</div>
                                    ${invoiceDiscountPercent > 0 ? `<div class="text-[10px] text-green-600">Disc ${invoiceDiscountPercent}% Applied</div>` : ''}
                                    <input type="hidden" class="input-price" value="${netPrice}">
                                </td>
                                <td class="text-center text-sm text-slate-600">
                                    ${parseFloat(item.quantity)}
                                </td>
                                <td class="text-center text-sm text-slate-600">
                                    ${parseFloat(item.quantity_returned)}
                                </td>
                                <td>
                                    <input type="number" 
                                           name="items[${index}][quantity]" 
                                           class="form-input input-qty text-center h-9" 
                                           min="0" 
                                           max="${maxQty}" 
                                           step="1"
                                           placeholder="0"
                                           data-max="${maxQty}">
                                    <div class="text-[10px] text-red-500 mt-1 hidden error-msg">Maks ${maxQty}</div>
                                </td>
                                <td class="text-right font-medium text-slate-700 dark:text-white">
                                    <span class="row-subtotal">0</span>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });

                        if (tableBody.children.length === 0) {
                             tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-slate-500 italic">Semua item pada invoice ini sudah diretur sepenuhnya.</td></tr>`;
                        }
                    }

                    // Attach Listeners for Calculation
                    attachCalculationListeners();

                } catch (error) {
                    console.error(error);
                    showToast('Gagal memuat data invoice: ' + error.message, 'error');
                    resetTable();
                } finally {
                    loadingIndicator.classList.add('hidden');
                }
            }

            function attachCalculationListeners() {
                const qtyInputs = document.querySelectorAll('.input-qty');
                
                qtyInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const row = this.closest('tr');
                        const price = parseFloat(row.querySelector('.input-price').value);
                        const max = parseFloat(this.dataset.max);
                        let qty = parseFloat(this.value);
                        const errorMsg = row.querySelector('.error-msg');

                        if (isNaN(qty)) qty = 0;

                        // Validasi Max
                        if (qty > max) {
                            this.classList.add('border-red-500', 'focus:ring-red-500');
                            errorMsg.classList.remove('hidden');
                            // Optional: Reset to max or keep user input but invalidate form
                        } else {
                            this.classList.remove('border-red-500', 'focus:ring-red-500');
                            errorMsg.classList.add('hidden');
                        }

                        // Calculate Subtotal
                        const subtotal = qty * price;
                        
                        // Format Currency
                        row.querySelector('.row-subtotal').textContent = new Intl.NumberFormat('id-ID').format(subtotal);

                        updateGrandTotal();
                    });
                });
            }

            function updateGrandTotal() {
                let total = 0;
                document.querySelectorAll('.input-qty').forEach(input => {
                    const row = input.closest('tr');
                    const price = parseFloat(row.querySelector('.input-price').value);
                    let qty = parseFloat(input.value);
                    if (isNaN(qty)) qty = 0;
                    total += (qty * price);
                });

                grandTotalDisplay.textContent = new Intl.NumberFormat('id-ID').format(total);
            }

            // Validasi sebelum submit
            document.getElementById('create-return-form').addEventListener('submit', function(e) {
                let hasItems = false;
                let hasError = false;

                document.querySelectorAll('.input-qty').forEach(input => {
                    const qty = parseFloat(input.value);
                    const max = parseFloat(input.dataset.max);
                    
                    if (qty > 0) hasItems = true;
                    if (qty > max) hasError = true;
                });

                if (hasError) {
                    e.preventDefault();
                    showToast('Harap perbaiki jumlah retur yang melebihi batas.', 'error');
                    return;
                }

                if (!hasItems) {
                    e.preventDefault();
                    showToast('Harap isi minimal satu item untuk diretur.', 'warning');
                    return;
                }
                
                // Button Loading State (handled by app.js default listener, but just in case)
                // logic 'is-loading' is centralized in app.js
            });
        });
    </script>
    @endpush
@endsection
@extends('admin.layouts.app')

@section('title', 'Edit Pesanan')

@section('content')

    <div class="max-w-6xl mx-auto">

        <form action="{{ route('admin.sales-orders.update', $order->order_id) }}" method="POST" id="orderForm">
            @csrf
            @method('PUT')

            {{-- HEADER --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="page-title">Edit Pesanan: {{ $order->order_number }}</h1>
                    <p class="page-subtitle">Perbarui rincian pesanan yang masih pending.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.sales-orders.index') }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                    </button>
                </div>
            </div>

            <div class="space-y-6">
                
                {{-- 1. INFO --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Informasi Pesanan</h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div>
                            <label class="form-label label-required">Pelanggan</label>
                            <select name="client_id" class="tom-select" required>
                                @foreach($clients as $client)
                                    <option value="{{ $client->client_id }}" {{ old('client_id', $order->client_id) == $client->client_id ? 'selected' : '' }}>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="form-label label-required">Tanggal Pesanan</label>
                            <input type="date" name="order_date" class="form-input" 
                                   value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" required>
                            @error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if(Auth::user()->hasRole(['admin', 'superadmin']))
                            <div>
                                <label class="form-label label-optional">Sales Representative</label>
                                <select name="sales_id" class="tom-select">
                                    <option value="">- Pilih Sales -</option>
                                    @foreach($salesUsers as $sales)
                                        <option value="{{ $sales->user_id }}" {{ old('sales_id', $order->user_id_sales) == $sales->user_id ? 'selected' : '' }}>
                                            {{ $sales->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="sales_id" value="{{ $order->user_id_sales }}">
                        @endif

                    </div>
                </div>

                {{-- 2. ITEMS --}}
                <div class="card">
                    <div class="card-header flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                        <h3 class="card-header-title">Item Pesanan</h3>
                        <button type="button" id="btnAddRow" class="btn btn-sm btn-secondary text-indigo-600 bg-indigo-50 border-indigo-200">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Barang
                        </button>
                    </div>
                    
                    {{-- REVISI: SCROLLABLE CONTAINER --}}
                    <div class="overflow-x-auto w-full rounded-b-xl">
                        <table class="table-modern w-full min-w-[800px]" id="itemsTable">
                            <thead>
                                <tr>
                                    <th class="w-[40%] min-w-[250px]">Produk</th>
                                    <th class="w-[20%] min-w-[150px] text-right">Harga Satuan</th>
                                    <th class="w-[15%] min-w-[100px] text-center">Qty</th>
                                    <th class="w-[20%] min-w-[150px] text-right">Subtotal</th>
                                    <th class="w-[5%] min-w-[50px] text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                {{-- Rows populated via JS --}}
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right font-bold uppercase text-xs text-slate-500">Total Akhir</td>
                                    <td class="px-6 py-4 text-right font-extrabold text-lg text-indigo-600 dark:text-indigo-400">
                                        <span id="grandTotalDisplay">Rp 0</span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- 3. NOTES --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Catatan Tambahan</h3>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" class="form-textarea h-24">{{ old('notes', $order->notes) }}</textarea>
                    </div>
                </div>

            </div>
        </form>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemsBody = document.getElementById('itemsBody');
        const btnAddRow = document.getElementById('btnAddRow');
        const grandTotalDisplay = document.getElementById('grandTotalDisplay');
        
        const productsData = @json($products); 
        const existingItems = @json($order->items); 
        let rowCount = 0;

        function addRow(data = null) {
            rowCount++;
            
            const selectedProductId = data ? data.product_id : '';
            const qtyValue = data ? parseFloat(data.quantity) : 1;
            
            let optionsHtml = '<option value="">Pilih Produk...</option>';
            productsData.forEach(prod => {
                const selected = prod.product_id == selectedProductId ? 'selected' : '';
                optionsHtml += `<option value="${prod.product_id}" data-price="${prod.selling_price}" ${selected}>${prod.product_name} (${prod.product_code})</option>`;
            });

            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition';
            tr.innerHTML = `
                <td class="p-3 align-top">
                    <select name="products[${rowCount}][product_id]" class="tom-select-dynamic product-select" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td class="p-3 align-top text-right">
                    <div class="text-sm font-mono text-slate-600 dark:text-slate-300 pt-3 price-display">Rp 0</div>
                    <input type="hidden" class="price-input" value="0">
                </td>
                <td class="p-3 align-top">
                    <input type="number" step="0.01" min="0.01" name="products[${rowCount}][quantity]" 
                           class="form-input text-center qty-input" value="${qtyValue}" required>
                </td>
                <td class="p-3 align-top text-right">
                    <div class="text-sm font-bold text-slate-800 dark:text-white pt-3 subtotal-display">Rp 0</div>
                </td>
                <td class="p-3 align-top text-center">
                    <button type="button" class="text-slate-400 hover:text-rose-500 transition-colors btn-remove-row pt-2">
                        <i class="material-icons text-lg">close</i>
                    </button>
                </td>
            `;
            itemsBody.appendChild(tr);

            const selectEl = tr.querySelector('.tom-select-dynamic');
            new TomSelect(selectEl, {
                sortField: { field: "text", direction: "asc" },
                plugins: ['clear_button'],
                dropdownParent: 'body',
                onChange: function(value) {
                    updateRowPrice(tr, value);
                }
            });

            if (selectedProductId) {
                updateRowPrice(tr, selectedProductId);
            }
        }

        // --- Logic JS Kalkulasi (Sama persis) ---
        function updateRowPrice(row, productId) {
            const product = productsData.find(p => p.product_id == productId);
            const priceInput = row.querySelector('.price-input');
            const priceDisplay = row.querySelector('.price-display');

            if (product) {
                const price = parseFloat(product.selling_price);
                priceInput.value = price;
                priceDisplay.innerText = formatRupiah(price);
            } else {
                priceInput.value = 0;
                priceDisplay.innerText = 'Rp 0';
            }
            calculateRow(row);
        }

        function calculateRow(row) {
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const subtotal = price * qty;
            row.querySelector('.subtotal-display').innerText = formatRupiah(subtotal);
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let total = 0;
            document.querySelectorAll('#itemsBody tr').forEach(row => {
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                total += price * qty;
            });
            grandTotalDisplay.innerText = formatRupiah(total);
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        itemsBody.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove-row')) {
                e.target.closest('tr').remove();
                calculateGrandTotal();
            }
        });

        itemsBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty-input')) {
                calculateRow(e.target.closest('tr'));
            }
        });

        btnAddRow.addEventListener('click', () => addRow());

        if (existingItems && existingItems.length > 0) {
            existingItems.forEach(item => {
                addRow(item);
            });
        } else {
            addRow();
        }
    });
</script>
@endpush
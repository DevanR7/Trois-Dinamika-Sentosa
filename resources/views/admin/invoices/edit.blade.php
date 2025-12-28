@extends('admin.layouts.app')

@section('title', 'Edit Invoice ' . $invoice->invoice_number)

@section('content')

    <div class="max-w-6xl mx-auto">
        
        {{-- Tombol Kembali --}}
        <div class="mb-6">
            <a href="{{ route('admin.invoices.show', $invoice->invoice_id) }}" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition-colors w-fit">
                <i class="material-icons text-base">arrow_back</i>
                <span class="text-sm font-medium">Kembali ke Detail</span>
            </a>
        </div>

        <form action="{{ route('admin.invoices.update', $invoice->invoice_id) }}" method="POST" id="invoiceForm">
            @csrf
            @method('PUT')

            {{-- HEADER --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="page-title">Edit Invoice: {{ $invoice->invoice_number }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="page-subtitle">Status:</span>
                        <span class="badge badge-secondary">{{ ucfirst($invoice->status) }}</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="material-icons text-sm mr-2">save</i> Simpan Perubahan
                </button>
            </div>

            <div class="space-y-6">
                
                {{-- 1. INFO --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-header-title">Informasi Faktur</h3>
                    </div>
                    <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="form-label label-required">Pelanggan</label>
                            <select name="client_id" class="tom-select" required>
                                @foreach($clients as $client)
                                    <option value="{{ $client->client_id }}" {{ old('client_id', $invoice->client_id) == $client->client_id ? 'selected' : '' }}>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4 md:col-span-2">
                            <div>
                                <label class="form-label label-required">Tanggal Invoice</label>
                                <input type="date" name="order_date" class="form-input" 
                                       value="{{ old('order_date', $invoice->order_date->format('Y-m-d')) }}" required>
                            </div>
                            <div>
                                <label class="form-label label-required">Jatuh Tempo</label>
                                <input type="date" name="due_date" class="form-input" 
                                       value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. ITEMS --}}
                <div class="card">
                    <div class="card-header flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                        <h3 class="card-header-title">Item Produk</h3>
                        <button type="button" id="btnAddRow" class="btn btn-sm btn-secondary text-indigo-600 bg-indigo-50 border-indigo-200">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Barang
                        </button>
                    </div>
                    
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
                        </table>
                    </div>
                </div>

                {{-- 3. BIAYA TAMBAHAN --}}
                <div class="card">
                    <div class="card-header flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                        <h3 class="card-header-title">Biaya Tambahan</h3>
                        <button type="button" id="btnAddCost" class="btn btn-sm btn-secondary text-emerald-600 bg-emerald-50 border-emerald-200">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Biaya
                        </button>
                    </div>
                    <div class="card-body bg-slate-50/50 dark:bg-slate-800/20">
                        <div id="additionalCostsContainer" class="space-y-3">
                            {{-- Cost Rows JS --}}
                        </div>
                        <div id="noCostPlaceholder" class="text-center text-sm text-slate-400 py-2 italic" style="display: none;">
                            Belum ada biaya tambahan.
                        </div>
                    </div>
                </div>

                {{-- 4. SPLIT LAYOUT: CATATAN & RINGKASAN --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    {{-- KIRI: Catatan --}}
                    <div class="card h-full">
                        <div class="card-header">
                            <h3 class="card-header-title">Catatan Faktur</h3>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" class="form-textarea h-full min-h-[180px]">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>
                    </div>

                    {{-- KANAN: Ringkasan --}}
                    <div class="card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm h-full">
                        <div class="card-header bg-slate-50 dark:bg-slate-800/80 border-b border-slate-100 dark:border-slate-700">
                            <h3 class="card-header-title text-slate-800 dark:text-white">Ringkasan Pembayaran</h3>
                        </div>
                        <div class="card-body space-y-3">
                            
                            {{-- Subtotal --}}
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600 dark:text-slate-400">Subtotal Item</span>
                                <span class="font-bold font-mono text-slate-800 dark:text-slate-200" id="subtotalDisplay">Rp 0</span>
                            </div>

                            {{-- Total Biaya --}}
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600 dark:text-slate-400">Total Biaya Tambahan</span>
                                <span class="font-bold font-mono text-slate-800 dark:text-slate-200" id="totalCostDisplay">Rp 0</span>
                            </div>

                            {{-- Diskon --}}
                            <div class="flex justify-between items-center text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-600 dark:text-slate-400">Diskon Global</span>
                                    <div class="input-group w-20">
                                        <input type="number" name="discount_percentage" id="discountInput" 
                                               class="form-input text-right py-1 px-2 text-xs h-7" 
                                               min="0" max="100" step="0.01" value="{{ old('discount_percentage', $invoice->discount_percentage) }}">
                                        <span class="input-group-text px-1 text-[10px]">%</span>
                                    </div>
                                </div>
                                <span class="font-bold font-mono text-rose-500" id="discountAmountDisplay">- Rp 0</span>
                            </div>

                            {{-- Pajak --}}
                            <div class="flex justify-between items-start text-sm pt-1">
                                <span class="text-slate-600 dark:text-slate-400 mt-2">Pajak (PPN/PPh)</span>
                                <div class="w-1/2 text-right">
                                    <select name="taxes[]" id="taxInput" class="tom-select" multiple>
                                        @php $selectedTaxes = $invoice->taxes->pluck('id')->toArray(); @endphp
                                        @foreach($taxes as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ in_array($tax->id, $selectedTaxes) ? 'selected' : '' }}>
                                                {{ $tax->name }} ({{ $tax->rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-xs text-emerald-600 font-mono mt-1" id="taxAmountDisplay">+ Rp 0</div>
                                </div>
                            </div>

                            <hr class="border-slate-200 dark:border-slate-700 my-2">

                            {{-- Grand Total --}}
                            <div class="flex justify-between items-center">
                                <span class="text-base font-extrabold text-slate-800 dark:text-white uppercase">Grand Total</span>
                                <span class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400" id="grandTotalDisplay">Rp 0</span>
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </form>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Elements
        const itemsBody = document.getElementById('itemsBody');
        const costsContainer = document.getElementById('additionalCostsContainer');
        const noCostPlaceholder = document.getElementById('noCostPlaceholder');
        const productsData = @json($products);
        
        // Load Existing
        const existingItems = @json($invoice->items);
        const existingCosts = @json($invoice->additionalCosts);
        
        let rowCount = 0;
        let costCount = 0;

        // --- ITEM FUNCTIONS (Sama) ---
        function addRow(data = null) {
            rowCount++;
            const selectedProductId = data ? data.product_id : '';
            const qtyValue = data ? parseFloat(data.quantity) : 1;
            const priceValue = data ? parseFloat(data.price_per_unit) : 0;
            
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
                    <div class="input-group">
                        <span class="input-group-text px-2 text-xs">Rp</span>
                        <input type="text" name="products[${rowCount}][custom_price]" 
                               class="form-input text-right autonumeric price-input" value="${priceValue}" required>
                    </div>
                    <label class="inline-flex items-center mt-1 cursor-pointer">
                        <input type="checkbox" name="products[${rowCount}][update_master_price]" value="1" class="form-checkbox w-3 h-3 text-indigo-600 rounded">
                        <span class="ml-1 text-[10px] text-slate-500">Update Master</span>
                    </label>
                </td>
                <td class="p-3 align-top">
                    <input type="number" step="0.01" min="0.01" name="products[${rowCount}][quantity]" 
                           class="form-input text-center qty-input" value="${qtyValue}" required>
                </td>
                <td class="p-3 align-top text-right">
                    <div class="text-sm font-bold text-slate-800 dark:text-white pt-2 subtotal-display">Rp 0</div>
                </td>
                <td class="p-3 align-top text-center">
                    <button type="button" class="text-slate-400 hover:text-rose-500 transition-colors btn-remove-row pt-1">
                        <i class="material-icons text-lg">close</i>
                    </button>
                </td>
            `;
            
            itemsBody.appendChild(tr);

            new TomSelect(tr.querySelector('.tom-select-dynamic'), {
                sortField: { field: "text", direction: "asc" },
                plugins: ['clear_button'],
                dropdownParent: 'body',
                onChange: function(value) {
                    if(!data) updateRowPrice(tr, value);
                }
            });

            new AutoNumeric(tr.querySelector('.price-input'), window.defaultAutoNumericOptions);
            
            tr.querySelector('.price-input').addEventListener('keyup', () => calculateRow(tr));
            tr.querySelector('.price-input').addEventListener('autoNumeric:rawValueModified', () => calculateRow(tr));

            calculateRow(tr);
        }

        // --- COST FUNCTIONS (Sama) ---
        function addCostRow(data = null) {
            costCount++;
            noCostPlaceholder.style.display = 'none';

            const desc = data ? data.description : '';
            const amount = data ? data.amount : 0;

            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 cost-row bg-white dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-700';
            div.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="additional_costs[${costCount}][description]" class="form-input text-xs w-full" value="${desc}" placeholder="Keterangan (Cth: Ongkir)" required>
                </div>
                <div class="input-group w-40">
                    <span class="input-group-text px-2 text-xs">Rp</span>
                    <input type="text" name="additional_costs[${costCount}][amount]" class="form-input text-right text-xs autonumeric cost-input" value="${amount}" placeholder="0" required>
                </div>
                <button type="button" class="text-slate-400 hover:text-rose-500 btn-remove-cost">
                    <i class="material-icons text-sm">close</i>
                </button>
            `;
            costsContainer.appendChild(div);
            new AutoNumeric(div.querySelector('.cost-input'), window.defaultAutoNumericOptions);
            div.querySelector('.cost-input').addEventListener('autoNumeric:rawValueModified', calculateTotals);
        }

        // --- CALCULATIONS ---
        function updateRowPrice(row, productId) {
            const product = productsData.find(p => p.product_id == productId);
            const priceInput = row.querySelector('.price-input');
            if (product) {
                AutoNumeric.getAutoNumericElement(priceInput).set(product.selling_price);
            } else {
                AutoNumeric.getAutoNumericElement(priceInput).set(0);
            }
            calculateRow(row);
        }

        function calculateRow(row) {
            const price = parseFloat(AutoNumeric.getAutoNumericElement(row.querySelector('.price-input')).getNumericString() || 0);
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const subtotal = price * qty;
            row.querySelector('.subtotal-display').innerText = formatRupiah(subtotal);
            calculateTotals();
        }

        function calculateTotals() {
            // 1. Subtotal Items
            let subtotal = 0;
            document.querySelectorAll('#itemsBody tr').forEach(row => {
                const price = parseFloat(AutoNumeric.getAutoNumericElement(row.querySelector('.price-input')).getNumericString() || 0);
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                subtotal += price * qty;
            });

            // 2. Costs
            let totalCosts = 0;
            document.querySelectorAll('.cost-input').forEach(input => {
                totalCosts += parseFloat(AutoNumeric.getAutoNumericElement(input).getNumericString() || 0);
            });

            // 3. Discount
            const discPercent = parseFloat(document.getElementById('discountInput').value) || 0;
            const discAmount = subtotal * (discPercent / 100);
            const afterDisc = subtotal - discAmount;

            // 4. Tax
            let totalTax = 0;
            const taxSelect = document.getElementById('taxInput');
            if(taxSelect.tomselect) {
                const selectedIds = taxSelect.tomselect.getValue();
                selectedIds.forEach(id => {
                     const option = taxSelect.querySelector(`option[value="${id}"]`);
                     if(option) {
                         const rate = parseFloat(option.dataset.rate);
                         totalTax += afterDisc * (rate / 100);
                     }
                });
            }

            const grandTotal = afterDisc + totalTax + totalCosts;

            // Update UI
            document.getElementById('subtotalDisplay').innerText = formatRupiah(subtotal);
            document.getElementById('totalCostDisplay').innerText = formatRupiah(totalCosts);
            document.getElementById('discountAmountDisplay').innerText = '- ' + formatRupiah(discAmount);
            document.getElementById('taxAmountDisplay').innerText = '+ ' + formatRupiah(totalTax);
            document.getElementById('grandTotalDisplay').innerText = formatRupiah(grandTotal);
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        // --- EVENT LISTENERS ---
        itemsBody.addEventListener('click', function(e) { if (e.target.closest('.btn-remove-row')) { e.target.closest('tr').remove(); calculateTotals(); } });
        itemsBody.addEventListener('input', function(e) { if (e.target.classList.contains('qty-input')) { calculateRow(e.target.closest('tr')); } });
        
        costsContainer.addEventListener('click', function(e) {
             if (e.target.closest('.btn-remove-cost')) {
                e.target.closest('.cost-row').remove();
                calculateTotals();
                if(costsContainer.children.length === 0) noCostPlaceholder.style.display = 'block';
            }
        });

        document.getElementById('btnAddRow').addEventListener('click', () => addRow());
        document.getElementById('btnAddCost').addEventListener('click', () => addCostRow());
        document.getElementById('discountInput').addEventListener('input', calculateTotals);
        document.getElementById('taxInput').addEventListener('change', calculateTotals);

        // --- INIT DATA ---
        if (existingItems && existingItems.length > 0) existingItems.forEach(item => addRow(item)); else addRow();
        if (existingCosts && existingCosts.length > 0) existingCosts.forEach(cost => addCostRow(cost)); else noCostPlaceholder.style.display = 'block';
        
        setTimeout(calculateTotals, 500); 
    });
</script>
@endpush
@extends('admin.layouts.app')

@section('title', 'Revisi Otomatis Invoice')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        <form action="{{ route('admin.invoice-adjustments.store.auto', $invoice->invoice_id) }}" method="POST" id="adjustmentForm">
            @csrf

            {{-- HEADER --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="page-title">Revisi Otomatis: {{ $invoice->invoice_number }}</h1>
                    <p class="page-subtitle">Ubah rincian di bawah. Sistem akan menghitung selisih (adjustment) secara otomatis.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.invoice-adjustments.create', ['invoice_id' => $invoice->invoice_id]) }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons text-sm mr-2">save</i> Simpan Revisi
                    </button>
                </div>
            </div>

            {{-- ALERT INFO --}}
            <div class="alert alert-info bg-indigo-50 border-indigo-100 text-indigo-900 mb-6 flex items-start gap-3 p-4 rounded-xl">
                <i class="material-icons text-indigo-500 mt-0.5">auto_fix_high</i>
                <div class="text-sm">
                    <strong>Cara Kerja:</strong> Edit daftar barang, biaya, atau pajak di bawah ini sesuai kondisi seharusnya. <br>
                    Sistem akan membandingkan <strong>Grand Total Baru</strong> dengan <strong>Total Lama (Rp {{ number_format($invoice->total_amount,0,',','.') }})</strong> 
                    dan membuat Nota Kredit/Debit atas selisihnya.
                </div>
            </div>

            <div class="space-y-6">
                
                {{-- 1. ITEM PRODUK --}}
                <div class="card">
                    <div class="card-header flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                        <h3 class="card-header-title">1. Item Produk (Baru)</h3>
                        <button type="button" id="btnAddRow" class="btn btn-sm btn-secondary text-indigo-600 bg-indigo-50 border-indigo-200 hover:bg-indigo-100 transition-colors">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Barang
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto w-full rounded-b-xl">
                        <table class="table-modern w-full min-w-[900px]" id="itemsTable">
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

                {{-- 2. BIAYA TAMBAHAN --}}
                <div class="card">
                    <div class="card-header flex justify-between items-center bg-slate-50 dark:bg-slate-800">
                        <h3 class="card-header-title">2. Biaya Tambahan</h3>
                        <button type="button" id="btnAddCost" class="btn btn-sm btn-secondary text-emerald-600 bg-emerald-50 border-emerald-200 hover:bg-emerald-100 transition-colors">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Biaya
                        </button>
                    </div>
                    <div class="card-body bg-slate-50/50 dark:bg-slate-800/20">
                        <div id="additionalCostsContainer" class="space-y-3">
                            {{-- Cost rows added via JS --}}
                        </div>
                        <div id="noCostPlaceholder" class="text-center text-sm text-slate-400 py-2 italic" style="display: none;">
                            Belum ada biaya tambahan.
                        </div>
                    </div>
                </div>

                {{-- 3. SPLIT LAYOUT: ALASAN & RINGKASAN --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    {{-- KIRI: Alasan & Opsi Overpayment --}}
                    <div class="space-y-6">
                        <div class="card h-full flex flex-col">
                            <div class="card-header">
                                <h3 class="card-header-title">Alasan Revisi</h3>
                            </div>
                            <div class="card-body flex flex-col gap-4 flex-1">
                                
                                {{-- Textarea mengisi sisa ruang --}}
                                <div class="flex-1">
                                    <textarea name="notes" class="form-textarea w-full h-full min-h-[120px] resize-none" 
                                              placeholder="Wajib diisi: Contoh: Salah input jumlah barang, perubahan harga dari pusat..." required></textarea>
                                </div>
                                
                                {{-- PERBAIKAN TAMPILAN CREDIT NOTE --}}
                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                                    <div class="flex gap-3">
                                        <div class="shrink-0">
                                            <i class="material-icons text-amber-600">info</i>
                                        </div>
                                        <div class="w-full">
                                            <p class="text-xs font-bold text-amber-800 mb-2 uppercase tracking-wide">
                                                Jika Total Baru Lebih Kecil (Credit Note):
                                            </p>
                                            
                                            <div class="space-y-2">
                                                <label class="flex items-center p-2 rounded-lg border border-amber-200 bg-white cursor-pointer hover:bg-amber-100/50 transition-colors">
                                                    <input type="radio" name="overpayment_action" value="deposit" 
                                                           class="form-radio text-amber-600 w-4 h-4 focus:ring-amber-500" checked>
                                                    <span class="ml-3 text-sm text-slate-700 font-medium">
                                                        Simpan selisih ke <b>Deposit Pelanggan</b>
                                                    </span>
                                                </label>
                                                
                                                <label class="flex items-center p-2 rounded-lg border border-transparent hover:bg-amber-100/50 cursor-pointer transition-colors">
                                                    <input type="radio" name="overpayment_action" value="refund" 
                                                           class="form-radio text-amber-600 w-4 h-4 focus:ring-amber-500">
                                                    <span class="ml-3 text-sm text-slate-600">
                                                        Akan di-Refund Manual (Tidak masuk deposit)
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- END PERBAIKAN --}}

                            </div>
                        </div>
                    </div>

                    {{-- KANAN: Ringkasan & Kalkulasi --}}
                    <div class="card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm h-full">
                        <div class="card-header bg-slate-50 dark:bg-slate-800/80 border-b border-slate-100 dark:border-slate-700">
                            <h3 class="card-header-title text-slate-800 dark:text-white">Ringkasan (Kalkulasi Baru)</h3>
                        </div>
                        <div class="card-body space-y-4">
                            
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
                                    <select name="taxes[]" id="taxInput" class="tom-select" multiple placeholder="Pilih Pajak...">
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

                            {{-- Grand Total Baru --}}
                            <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-700/50 p-3 rounded-lg">
                                <span class="text-sm font-extrabold text-slate-800 dark:text-white uppercase">Grand Total Baru</span>
                                <span class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400" id="grandTotalDisplay">Rp 0</span>
                            </div>

                            {{-- Selisih (Diff) --}}
                            <div class="flex justify-between items-center p-3 rounded-lg border border-dashed border-slate-300 dark:border-slate-600">
                                <span class="text-xs font-bold text-slate-500 uppercase">Selisih (Adjustment)</span>
                                <span class="text-base font-mono font-bold" id="diffDisplay">Rp 0</span>
                            </div>
                            <div class="text-[10px] text-center text-slate-400">
                                Total Lama: Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
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
        const itemsBody = document.getElementById('itemsBody');
        const costsContainer = document.getElementById('additionalCostsContainer');
        const noCostPlaceholder = document.getElementById('noCostPlaceholder');
        const grandTotalDisplay = document.getElementById('grandTotalDisplay');
        const diffDisplay = document.getElementById('diffDisplay');
        
        const productsData = @json($products);
        const existingItems = @json($invoice->items);
        const existingCosts = @json($invoice->additionalCosts);
        const oldTotal = {{ $invoice->total_amount }};
        
        let rowCount = 0;
        let costCount = 0;

        function addRow(data = null) {
            rowCount++;
            const selectedProductId = data ? data.product_id : '';
            const qtyValue = data ? parseFloat(data.quantity) : 1;
            const priceValue = data ? parseFloat(data.price_per_unit) : 0;
            
            let optionsHtml = '<option value="">Pilih Produk...</option>';
            productsData.forEach(prod => {
                const selected = prod.product_id == selectedProductId ? 'selected' : '';
                optionsHtml += `<option value="${prod.product_id}" data-price="${prod.selling_price}" ${selected}>${prod.product_name}</option>`;
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
                        <input type="text" name="products[${rowCount}][price_per_unit]" 
                               class="form-input text-right autonumeric price-input" value="${priceValue}" required>
                    </div>
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
            let subtotal = 0;
            document.querySelectorAll('#itemsBody tr').forEach(row => {
                const price = parseFloat(AutoNumeric.getAutoNumericElement(row.querySelector('.price-input')).getNumericString() || 0);
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                subtotal += price * qty;
            });

            let totalCosts = 0;
            document.querySelectorAll('.cost-input').forEach(input => {
                totalCosts += parseFloat(AutoNumeric.getAutoNumericElement(input).getNumericString() || 0);
            });

            const discPercent = parseFloat(document.getElementById('discountInput').value) || 0;
            const discAmount = subtotal * (discPercent / 100);
            const afterDisc = subtotal - discAmount;

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
            const diff = grandTotal - oldTotal;

            document.getElementById('subtotalDisplay').innerText = formatRupiah(subtotal);
            document.getElementById('totalCostDisplay').innerText = formatRupiah(totalCosts);
            document.getElementById('discountAmountDisplay').innerText = '- ' + formatRupiah(discAmount);
            document.getElementById('taxAmountDisplay').innerText = '+ ' + formatRupiah(totalTax);
            document.getElementById('grandTotalDisplay').innerText = formatRupiah(grandTotal);

            if (diff > 0) {
                diffDisplay.innerHTML = `<span class="text-rose-600">+ ${formatRupiah(Math.abs(diff))} (Kurang Bayar)</span>`;
            } else if (diff < 0) {
                diffDisplay.innerHTML = `<span class="text-emerald-600">- ${formatRupiah(Math.abs(diff))} (Lebih Bayar)</span>`;
            } else {
                diffDisplay.innerHTML = `<span class="text-slate-400">0 (Tidak ada perubahan)</span>`;
            }
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

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

        if (existingItems && existingItems.length > 0) {
            existingItems.forEach(item => addRow(item));
        } else {
            addRow();
        }
        
        if (existingCosts && existingCosts.length > 0) {
            existingCosts.forEach(cost => addCostRow(cost));
        } else {
            noCostPlaceholder.style.display = 'block';
        }
        
        setTimeout(calculateTotals, 500); 
    });
</script>
@endpush
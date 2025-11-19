@extends("layouts.app")

@section("content")
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white"><h4 class="mb-0">Buat Invoice Baru</h4></div>
                <div class="card-body p-4">
                    @if ($errors->any() || session("error"))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                @if (session("error"))<li>{{ session("error") }}</li>@endif
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route("invoices.store") }}" method="POST" id="invoice-form">
                        @csrf
                        @if (isset($order))
                            <input type="hidden" name="sales_order_id" value="{{ $order->order_id }}">
                        @endif
                        
                        {{-- BAGIAN HEADER --}}
                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <label for="client_id" class="form-label fw-semibold">Pilih Klien</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Klien --</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->client_id }}" @selected(old("client_id", isset($order) ? $order->client_id : '') == $client->client_id)>
                                            {{ $client->client_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if (isset($order))
                                    <input type="hidden" name="client_id" value="{{ $order->client_id }}" />
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label for="order_date" class="form-label fw-semibold">Tanggal Pesanan</label>
                                <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old("order_date", now()->format("Y-m-d")) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label for="due_date" class="form-label fw-semibold">Tanggal Jatuh Tempo</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old("due_date", now()->addDays(30)->format("Y-m-d")) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label for="user_id_sales" class="form-label fw-semibold">Pilih Sales (Opsional)</label>
                                <select name="user_id_sales" id="user_id_sales" class="form-select">
                                    <option value="">-- Umum / Tanpa Sales --</option>
                                    @foreach ($salesUsers as $sales)
                                        <option value="{{ $sales->user_id }}" @selected(old('user_id_sales', isset($order) ? $order->user_id_sales : null) == $sales->user_id)>
                                            {{ $sales->full_name }} ({{ $sales->sales_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- BAGIAN PRODUK --}}
                        <h5 class="fw-semibold mb-3">Rincian Item Produk</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%">Produk</th>
                                        <th style="width: 10%">Kuantitas</th>
                                        <th style="width: 25%">Harga Satuan (Rp)</th> 
                                        <th class="text-end" style="width: 20%">Subtotal</th>
                                        <th class="text-center" style="width: 10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="product-items"></tbody>
                            </table>
                        </div>
                        <button type="button" id="add-product-btn" class="btn btn-secondary btn-sm"><i class="bi bi-plus-circle me-1"></i> Tambah Produk</button>

                        <hr class="my-4" />

                        {{-- BAGIAN BIAYA TAMBAHAN --}}
                        <h5 class="fw-semibold mb-3">Biaya Tambahan (Opsional)</h5>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Keterangan Biaya</th>
                                        <th style="width: 25%">Nominal (Rp)</th>
                                        <th class="text-center" style="width: 10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="additional-cost-items">
                                    {{-- Row biaya tambahan akan muncul di sini --}}
                                </tbody>
                            </table>
                        </div>
                        <button type="button" id="add-cost-btn" class="btn btn-outline-dark btn-sm"><i class="bi bi-cash-stack me-1"></i> Tambah Biaya Lain</button>

                        <hr class="my-4" />

                        {{-- BAGIAN TOTAL & NOTES --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label fw-semibold">Catatan (Opsional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                                </div>
                                <h5 class="fw-semibold mb-3 mt-4">Pajak</h5>
                                <div id="tax-options">
                                    @forelse ($taxes as $tax)
                                        <div class="form-check">
                                            <input class="form-check-input tax-checkbox" type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax{{ $tax->id }}" data-rate="{{ $tax->rate }}" />
                                            <label class="form-check-label" for="tax{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</label>
                                        </div>
                                    @empty
                                        <p class="text-muted">Tidak ada data pajak aktif.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="fw-semibold mb-3">Ringkasan Total</h5>
                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex justify-content-between mb-2"><span>Subtotal Produk</span><span id="subtotal-display">Rp 0</span></div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="discount_percentage" class="form-label mb-0">Diskon Global (%)</label>
                                        {{-- ✅ UPDATE: Default 60% --}}
                                        <input type="number" step="any" class="form-control form-control-sm" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', 60) }}" style="width: 80px;">
                                    </div>
                                    <div class="d-flex justify-content-between mb-2"><span class="text-danger">Potongan Diskon</span><span class="text-danger" id="discount-amount-display">Rp 0</span></div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-2 fw-semibold"><span>Subtotal (Stlh Diskon)</span><span id="subtotal-after-discount">Rp 0</span></div>
                                    <div id="tax-breakdown"></div>
                                    <div class="d-flex justify-content-between mb-2 text-secondary"><span>Total Biaya Tambahan</span><span id="total-additional-display">Rp 0</span></div>
                                    <hr />
                                    <h4 class="fw-bold d-flex justify-content-between"><span>Total Akhir</span><span id="grand-total" class="text-primary">Rp 0</span></h4>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route("invoices.index") }}" class="btn btn-light me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TEMPLATE ROW PRODUK (DIPERBARUI DENGAN AUTONUMERIC) --}}
<template id="product-row-template">
    <tr>
        <td>
            <select class="form-select form-select-sm product-select" required>
                <option value="" data-price="0" disabled selected>-- Pilih Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}"> {{ $product->product_name }} </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" class="form-control form-control-sm quantity" value="1" min="1" required /></td>
        <td>
            {{-- Input Text untuk Display (AutoNumeric) --}}
            <input type="text" class="form-control form-control-sm price-display" required placeholder="0" />
            
            {{-- Input Hidden untuk Value Asli (Dikirim ke Controller) --}}
            <input type="hidden" class="price-raw" value="0" />

            {{-- Checkbox Update Master --}}
            <div class="form-check mt-1 small">
                <input class="form-check-input update-master-check" type="checkbox" value="1" id="">
                <label class="form-check-label text-muted">Update Harga Master</label>
            </div>
        </td>
        <td class="text-end align-middle"><span class="subtotal">Rp 0</span></td>
        <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm remove-product-btn"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

{{-- TEMPLATE ROW BIAYA TAMBAHAN (DIPERBARUI DENGAN AUTONUMERIC) --}}
<template id="additional-cost-template">
    <tr>
        <td>
            <input type="text" class="form-control form-control-sm cost-desc" placeholder="Contoh: Biaya Packing" required>
        </td>
        <td>
            {{-- Input Text untuk Display (AutoNumeric) --}}
            <input type="text" class="form-control form-control-sm cost-display" required placeholder="0" />
            
            {{-- Input Hidden untuk Value Asli --}}
            <input type="hidden" class="cost-raw" value="0" />
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm remove-cost-btn"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
</template>

@endsection

@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script> 

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Setup Select2 ---
    $('#client_id').select2({ theme: 'bootstrap-5', placeholder: '-- Pilih Klien --' });

    // Pre-fill Order Data
    const orderItems = @json(isset($order) ? $order->items : []);
    const orderClientId = @json(isset($order) ? $order->client_id : null);

    if (orderClientId) {
        $('#client_id').val(orderClientId).trigger('change');
        $('#client_id').prop('disabled', true);
    }
    
    // --- Elements ---
    const productItemsContainer = document.getElementById('product-items');
    const additionalCostItemsContainer = document.getElementById('additional-cost-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const costRowTemplate = document.getElementById('additional-cost-template');
    
    const taxOptionsContainer = document.getElementById('tax-options');
    const discountInput = document.getElementById('discount_percentage');
    
    let productIndex = 0;
    let costIndex = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // --- Helper untuk Inisialisasi AutoNumeric ---
    function initAutoNumeric(element, hiddenElement) {
        const an = new AutoNumeric(element, {
            decimalCharacter: ',',
            digitGroupSeparator: '.',
            decimalPlaces: 0, // Sesuaikan jika ingin desimal
            minimumValue: '0',
            unformatOnSubmit: true // Opsional, tapi kita pakai hidden input
        });

        // Update hidden input saat nilai berubah
        element.addEventListener('autoNumeric:rawValueModified', e => {
            hiddenElement.value = e.detail.newRawValue;
            calculateTotals(); // Hitung ulang total
        });

        return an;
    }

    // --- Main Calculation Function ---
    function calculateTotals() {
        // 1. Hitung Subtotal Produk
        let subtotalProducts = 0;
        productItemsContainer.querySelectorAll('tr').forEach((row) => {
            // Ambil nilai dari input HIDDEN (.price-raw)
            const price = parseFloat(row.querySelector('.price-raw').value) || 0;
            const quantity = parseInt(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
        });

        // 2. Hitung Biaya Tambahan
        let totalAdditionalCosts = 0;
        additionalCostItemsContainer.querySelectorAll('tr').forEach((row) => {
            // Ambil nilai dari input HIDDEN (.cost-raw)
            const amount = parseFloat(row.querySelector('.cost-raw').value) || 0;
            totalAdditionalCosts += amount;
        });

        // 3. Hitung Diskon (Biasanya hanya dari produk)
        const discountRate = parseFloat(discountInput.value) || 0;
        const discountAmount = subtotalProducts * (discountRate / 100);
        const subtotalAfterDiscount = subtotalProducts - discountAmount;

        // 4. Hitung Pajak (Biasanya dari produk setelah diskon)
        let totalTaxAmount = 0;
        let taxHtml = '';
        taxOptionsContainer.querySelectorAll('.tax-checkbox:checked').forEach((checkbox) => {
            const rate = parseFloat(checkbox.dataset.rate) || 0;
            const name = checkbox.nextElementSibling.textContent.trim();
            const taxAmount = subtotalAfterDiscount * (rate / 100);
            totalTaxAmount += taxAmount;
            taxHtml += `<div class="d-flex justify-content-between mb-2"><span>${name}:</span> <span>${formatRupiah(taxAmount)}</span></div>`;
        });
        
        // 5. Grand Total
        const grandTotal = subtotalAfterDiscount + totalTaxAmount + totalAdditionalCosts;

        // 6. Render Tampilan
        document.getElementById('subtotal-display').textContent = formatRupiah(subtotalProducts);
        document.getElementById('discount-amount-display').textContent = `(-) ${formatRupiah(discountAmount)}`;
        document.getElementById('subtotal-after-discount').textContent = formatRupiah(subtotalAfterDiscount);
        document.getElementById('tax-breakdown').innerHTML = taxHtml;
        document.getElementById('total-additional-display').textContent = `(+) ${formatRupiah(totalAdditionalCosts)}`;
        document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
    }

    // --- Logic Produk ---
    function addProductRow() {
        const newRow = productRowTemplate.content.cloneNode(true).querySelector('tr');
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.quantity');
        
        const priceDisplayInput = newRow.querySelector('.price-display');
        const priceRawInput = newRow.querySelector('.price-raw');
        
        const updateMasterCheck = newRow.querySelector('.update-master-check');
        
        // Set name attributes (hanya untuk input yang dikirim ke server)
        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        // Name ditaruh di hidden input
        priceRawInput.name = `products[${productIndex}][custom_price]`; 
        updateMasterCheck.name = `products[${productIndex}][update_master_price]`;
        
        // Label for checkbox
        updateMasterCheck.id = `update_master_${productIndex}`;
        newRow.querySelector('label.form-check-label').setAttribute('for', `update_master_${productIndex}`);
        
        productItemsContainer.appendChild(newRow);

        // Init Select2
        const select2 = $(productSelect).select2({ 
            theme: 'bootstrap-5', 
            dropdownParent: $(productSelect).parent(),
            placeholder: '-- Pilih Produk --'
        });

        // Init AutoNumeric pada kolom harga
        const anPrice = initAutoNumeric(priceDisplayInput, priceRawInput);

        // Event Listeners Produk
        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const basePrice = parseFloat(selectedOption.dataset.price) || 0;
            
            // Set nilai AutoNumeric
            anPrice.set(basePrice);
            // Set nilai hidden input manual
            priceRawInput.value = basePrice;
            
            calculateTotals();
        });

        quantityInput.addEventListener('input', calculateTotals);

        newRow.querySelector('.remove-product-btn').addEventListener('click', () => {
            select2.select2('destroy');
            newRow.remove();
            calculateTotals();
        });
        
        productIndex++;
        return { newRow, anPrice, priceRawInput }; 
    }

    // --- Logic Biaya Tambahan ---
    function addCostRow() {
        const newRow = costRowTemplate.content.cloneNode(true).querySelector('tr');
        const descInput = newRow.querySelector('.cost-desc');
        
        const costDisplayInput = newRow.querySelector('.cost-display');
        const costRawInput = newRow.querySelector('.cost-raw');

        descInput.name = `additional_costs[${costIndex}][description]`;
        // Name ditaruh di hidden input
        costRawInput.name = `additional_costs[${costIndex}][amount]`;

        additionalCostItemsContainer.appendChild(newRow);

        // Init AutoNumeric
        initAutoNumeric(costDisplayInput, costRawInput);

        newRow.querySelector('.remove-cost-btn').addEventListener('click', () => {
            newRow.remove();
            calculateTotals();
        });

        costIndex++;
    }

    // --- Inisialisasi ---
    document.getElementById('add-product-btn').addEventListener('click', () => addProductRow());
    document.getElementById('add-cost-btn').addEventListener('click', () => addCostRow());
    taxOptionsContainer.addEventListener('change', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);

    // Load Order Items jika ada (misal dari Sales Order)
    if (orderItems.length > 0) {
        orderItems.forEach(item => {
            const { newRow, anPrice, priceRawInput } = addProductRow();
            const productSelect = newRow.querySelector('.product-select');
            const quantityInput = newRow.querySelector('.quantity');

            $(productSelect).val(item.product_id).trigger('change');
            quantityInput.value = item.quantity;
            
            // Set harga dari data order jika ada, atau biarkan default dari master
            // anPrice sudah otomatis ter-set oleh event listener 'change' select2 di atas
        });
    } else {
        addProductRow();
    }
    
    calculateTotals();
});
</script>
@endpush
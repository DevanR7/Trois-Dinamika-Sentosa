@extends("layouts.app")

@section("content")
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            Buat Invoice Baru
                            {{ isset($salesOrder) ? "dari Pesanan " . $salesOrder->order_number : "" }}
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session("error"))
                            <div class="alert alert-danger">
                                {{ session("error") }}
                            </div>
                        @endif

                        <form
                            action="{{ route("invoices.store") }}"
                            method="POST"
                        >
                            @csrf
                            @if (isset($salesOrder))
                                <input
                                    type="hidden"
                                    name="sales_order_id"
                                    value="{{ $salesOrder->order_id }}"
                                />
                            @endif

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label
                                        for="client_id"
                                        class="form-label fw-semibold"
                                    >
                                        Pilih Klien
                                    </label>
                                    <select
                                        name="client_id"
                                        id="client_id"
                                        class="form-select"
                                        required
                                        {{ isset($salesOrder) ? "disabled" : "" }}
                                    >
                                        <option value="" disabled selected>
                                            -- Pilih Klien --
                                        </option>
                                        @foreach ($clients as $client)
                                            <option
                                                value="{{ $client->client_id }}"
                                                {{ (isset($salesOrder) && $salesOrder->client_id == $client->client_id) || old("client_id") == $client->client_id ? "selected" : "" }}
                                            >
                                                {{ $client->client_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (isset($salesOrder))
                                        <input
                                            type="hidden"
                                            name="client_id"
                                            value="{{ $salesOrder->client_id }}"
                                        />
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label
                                        for="due_date"
                                        class="form-label fw-semibold"
                                    >
                                        Tanggal Jatuh Tempo
                                    </label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="due_date"
                                        name="due_date"
                                        value="{{ old("due_date", now()->addDays(30)->format("Y-m-d"),) }}"
                                        required
                                    />
                                </div>
                            </div>

                            <h5 class="fw-semibold mb-3">Rincian Item</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40%">Produk</th>
                                            <th style="width: 15%">
                                                Kuantitas
                                            </th>
                                            <th style="width: 20%">
                                                Harga Satuan
                                            </th>
                                            <th
                                                class="text-end"
                                                style="width: 20%"
                                            >
                                                Subtotal
                                            </th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="product-items">
                                        {{-- Baris produk dinamis akan ditambahkan di sini oleh JavaScript --}}
                                    </tbody>
                                </table>
                            </div>
                            <button
                                type="button"
                                id="add-product-btn"
                                class="btn btn-secondary btn-sm"
                            >
                                <i class="bi bi-plus-circle me-1"></i>
                                Tambah Item
                            </button>

                            <hr class="my-4" />

                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="fw-semibold mb-3">
                                        Biaya Tambahan / Pajak
                                    </h5>
                                    <div id="tax-options">
                                        @forelse ($taxes as $tax)
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input tax-checkbox"
                                                    type="checkbox"
                                                    name="taxes[]"
                                                    value="{{ $tax->id }}"
                                                    id="tax{{ $tax->id }}"
                                                    data-rate="{{ $tax->rate }}"
                                                />
                                                <label
                                                    class="form-check-label"
                                                    for="tax{{ $tax->id }}"
                                                >
                                                    {{ $tax->name }}
                                                    ({{ $tax->rate }}%)
                                                </label>
                                            </div>
                                        @empty
                                            <p class="text-muted">
                                                Tidak ada data pajak aktif.
                                            </p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="mb-2">
                                        <span class="fw-semibold">
                                            Subtotal Produk:
                                        </span>
                                        <span id="subtotal-display">Rp 0</span>
                                    </div>
                                    <div id="tax-breakdown">
                                        {{-- Rincian pajak akan muncul di sini --}}
                                    </div>
                                    <hr />
                                    <h4 class="fw-bold">
                                        Total:
                                        <span
                                            id="grand-total"
                                            class="text-primary"
                                        >
                                            Rp 0
                                        </span>
                                    </h4>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a
                                    href="{{ route("invoices.index") }}"
                                    class="btn btn-light me-2"
                                >
                                    Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    Simpan Invoice
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="product-row-template">
        <tr>
            <td>
                 <select class="form-select form-select-sm product-select product-select2" required>
                <option></option> {{-- Dikosongkan untuk placeholder Select2 --}}
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
            </td>
            <td>
                <input
                    type="number"
                    class="form-control form-control-sm quantity"
                    value="1"
                    min="1"
                    required
                />
            </td>
            <td>
                <input
                    type="text"
                    class="form-control form-control-sm price-display"
                    readonly
                />
                <input type="hidden" class="price-raw" />
            </td>
            <td class="text-end"><span class="subtotal">Rp 0</span></td>
            <td class="text-center">
                <button
                    type="button"
                    class="btn btn-danger btn-sm remove-product-btn"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@push("scripts")
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const salesOrderItems = @json(isset($salesOrder) ? $salesOrder->items : []);
        const productItemsContainer = document.getElementById('product-items');
        const productRowTemplate = document.getElementById('product-row-template');
        const addProductBtn = document.getElementById('add-product-btn');
        const taxOptionsContainer = document.getElementById('tax-options');
        let productIndex = 0;

            // --- FUNGSI-FUNGSI UTAMA ---

            // Fungsi untuk memformat angka ke Rupiah
            function formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                }).format(number);
            }

            // Fungsi pusat untuk menghitung semua total
            function calculateTotals() {
                let subtotalProducts = 0;
                productItemsContainer.querySelectorAll('tr').forEach((row) => {
                    const price =
                        parseFloat(row.querySelector('.price-raw').value) || 0;
                    const quantity =
                        parseInt(row.querySelector('.quantity').value) || 0;
                    const subtotal = price * quantity;
                    row.querySelector('.subtotal').textContent =
                        formatRupiah(subtotal);
                    subtotalProducts += subtotal;
                });

                let taxHtml = '';
                let totalTaxAmount = 0;
                taxOptionsContainer
                    .querySelectorAll('.tax-checkbox:checked')
                    .forEach((checkbox) => {
                        const rate = parseFloat(checkbox.dataset.rate) || 0;
                        const name =
                            checkbox.nextElementSibling.textContent.trim();
                        const taxAmountForItem =
                            subtotalProducts * (rate / 100);
                        totalTaxAmount += taxAmountForItem;
                        taxHtml += `<div class="mb-2"><span class="fw-semibold">${name}:</span> <span>${formatRupiah(taxAmountForItem)}</span></div>`;
                    });

                const grandTotal = subtotalProducts + totalTaxAmount;

                document.getElementById('subtotal-display').textContent =
                    formatRupiah(subtotalProducts);
                document.getElementById('tax-breakdown').innerHTML = taxHtml;
                document.getElementById('grand-total').textContent =
                    formatRupiah(grandTotal);
            }

              function addProductRow(item = null) {
            const newRowFragment = productRowTemplate.content.cloneNode(true);
            const newRow = newRowFragment.querySelector('tr');

            const productSelect = newRow.querySelector('.product-select');
            const quantityInput = newRow.querySelector('.quantity');
            const priceDisplay = newRow.querySelector('.price-display');
            const priceRaw = newRow.querySelector('.price-raw');
            const removeBtn = newRow.querySelector('.remove-product-btn');

            // Atur nama unik
            productSelect.name = `products[${productIndex}][product_id]`;
            quantityInput.name = `products[${productIndex}][quantity]`;
            priceRaw.name = `products[${productIndex}][price]`;
            
            productItemsContainer.appendChild(newRow);

            // --- PERUBAHAN UTAMA: INISIALISASI SELECT2 ---
            const select2 = $(productSelect).select2({
                placeholder: '-- Pilih Produk --',
                theme: 'bootstrap-5',
                dropdownParent: $(productSelect).parent() // Penting agar search box berfungsi
            });

            // Event listener untuk Select2
            select2.on('select2:select', function(e) {
                const selectedOption = e.params.data.element;
                const price = selectedOption.getAttribute('data-price') || 0;
                priceDisplay.value = formatRupiah(price);
                priceRaw.value = price;
                calculateTotals();
            });

            quantityInput.addEventListener('input', calculateTotals);
            removeBtn.addEventListener('click', () => {
                select2.select2('destroy'); // Hancurkan instance Select2
                newRow.remove();
                calculateTotals();
            });

            if (item) {
                $(productSelect).val(item.product_id).trigger('change.select2'); // Gunakan trigger change dari jQuery
                quantityInput.value = item.quantity;
                const price = item.price_per_unit;
                priceDisplay.value = formatRupiah(price);
                priceRaw.value = price;
            }
            
            productIndex++;
        }

        // --- Inisialisasi Halaman ---
        addProductBtn.addEventListener('click', () => addProductRow());
        taxOptionsContainer.addEventListener('change', calculateTotals);

        if (salesOrderItems.length > 0) {
            salesOrderItems.forEach(item => addProductRow(item));
        } else {
            addProductRow();
        }
        
        // Panggil kalkulasi total di akhir untuk memastikan semua sudah terhitung
        calculateTotals();
    });
    </script>
@endpush

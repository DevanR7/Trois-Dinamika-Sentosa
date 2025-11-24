@extends('layouts.app')

@section('title', 'Koreksi Otomatis Invoice')

@section('styles')
    {{-- Stylesheet untuk Select2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        /* Custom style agar Select2 menyatu dengan input Tailwind */
        .select2-container--bootstrap-5 .select2-selection {
            border-color: #d1d5db !important; /* gray-300 */
            padding-top: 0.35rem;
            padding-bottom: 0.35rem;
            border-radius: 0.5rem;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #6366f1 !important; /* indigo-500 */
            box-shadow: 0 0 0 1px #6366f1 !important;
        }
    </style>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('invoices.index') }}" class="hover:text-indigo-600 transition">Invoice</a>
                <span>/</span>
                <span class="text-gray-800">Koreksi</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Koreksi Otomatis</h2>
            <p class="text-sm text-gray-500 mt-1">
                Revisi untuk Invoice: <span class="font-mono font-bold text-indigo-600">{{ $invoice->invoice_number }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('invoice-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition text-sm">
                <i class="bi bi-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
            <i class="bi bi-x-circle-fill text-red-500 mt-0.5 text-lg"></i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- INFO BOX --}}
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3 shadow-sm">
        <i class="bi bi-info-circle-fill text-blue-500 mt-0.5 text-xl"></i>
        <div>
            <h3 class="text-sm font-bold text-blue-800">Mode Revisi Item</h3>
            <p class="text-sm text-blue-700 mt-1 leading-relaxed">
                Silakan ubah data di bawah ini sesuai kondisi riil. Sistem akan otomatis menghitung selisihnya (Nota Debet/Kredit) tanpa mengubah invoice asli secara langsung.
            </p>
        </div>
    </div>

    <form action="{{ route('invoice-adjustments.store.auto', $invoice->invoice_id) }}" method="POST" id="adjustment-form">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            {{-- KOLOM KIRI: FORM ITEM & DETAIL --}}
            <div class="xl:col-span-2 space-y-6">
                
                {{-- 1. INFORMASI READONLY --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center gap-2">
                        <i class="bi bi-file-earmark-text text-gray-400"></i>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Informasi Dasar (Read-only)</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Klien</label>
                            <input type="text" class="block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-600 sm:text-sm cursor-not-allowed" value="{{ $invoice->client->client_name }}" readonly>
                            <input type="hidden" name="client_id" value="{{ $invoice->client_id }}">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Invoice</label>
                            <input type="date" class="block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-600 sm:text-sm cursor-not-allowed" value="{{ optional($invoice->order_date)->format('Y-m-d') }}" readonly>
                            <input type="hidden" name="order_date" value="{{ optional($invoice->order_date)->format('Y-m-d') }}">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sales</label>
                            <input type="text" class="block w-full rounded-lg border-gray-300 bg-gray-100 text-gray-600 sm:text-sm cursor-not-allowed" value="{{ $invoice->sales->full_name ?? '-- Tanpa Sales --' }}" readonly>
                            <input type="hidden" name="user_id_sales" value="{{ $invoice->user_id_sales }}">
                            <input type="hidden" name="due_date" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
                        </div>

                    </div>
                </div>

                {{-- 2. TABEL ITEM --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                        <div class="flex items-center gap-2">
                            <i class="bi bi-box-seam text-gray-400"></i>
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Revisi Item</h3>
                        </div>
                        <button type="button" id="add-product-btn" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-full text-xs font-bold transition">
                            <i class="bi bi-plus-lg mr-1"></i> Tambah Item
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
    <tr>
        <th scope="col" class="px-3 py-3 text-center w-10">
            <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 cursor-pointer" id="header-row-select">
        </th>
        
        {{-- PRODUK: Hapus min-w-350px, ganti jadi min-w-[200px] agar fleksibel tapi aman --}}
        <th scope="col" class="px-3 py-3 text-left min-w-[200px]">
            Produk
        </th>
        
        {{-- QTY: w-24 (96px) sudah cukup untuk input angka 3 digit + spinner --}}
        <th scope="col" class="px-3 py-3 text-center w-24">
            Qty
        </th>
        
        {{-- HARGA: w-44 (176px) cukup luas untuk harga ratusan juta + checkbox --}}
        <th scope="col" class="px-3 py-3 text-right w-44">
            Harga Revisi (@)
        </th>
        
        {{-- SUBTOTAL: w-40 (160px) --}}
        <th scope="col" class="px-3 py-3 text-right w-40">
            Subtotal
        </th>
        
        <th scope="col" class="px-3 py-3 text-center w-10">
            <i class="bi bi-gear text-gray-400"></i>
        </th>
    </tr>
</thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="product-items">
                                {{-- JS akan mengisi row di sini --}}
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 border-t border-gray-200 bg-gray-50">
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Alasan Koreksi <span class="text-red-500">*</span></label>
                        <textarea name="reason" id="reason" rows="2" class="form-textarea w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Koreksi salah input harga, perubahan qty, pengembalian barang..." required>{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KALKULASI --}}
            <div class="xl:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                        <i class="bi bi-calculator text-gray-400"></i>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Kalkulasi Revisi</h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        
                        {{-- OPSI KALKULASI --}}
                        <div>
                            <label for="discount_percentage" class="block text-sm font-medium text-gray-700 mb-1">Diskon Global (%)</label>
                            <div class="relative rounded-md shadow-sm">
                                <input type="number" step="any" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $invoice->discount_percentage) }}" min="0" max="100" class="form-input block w-full rounded-lg border-gray-300 pr-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">%</span>
                                </div>
                            </div>
                        </div>

                        <div id="tax-options">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pajak</label>
                            <div class="space-y-2">
                                @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                    <div class="flex items-center">
                                        <input type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax{{ $tax->id }}" data-rate="{{ $tax->rate }}" @checked($invoice->taxes->contains($tax->id)) class="tax-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                        <label for="tax{{ $tax->id }}" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                                            {{ $tax->name }} ({{ $tax->rate }}%)
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr class="border-dashed border-gray-300">

                        {{-- SUMMARY BOX --}}
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Subtotal Barang</span>
                                <span class="font-medium text-gray-900" id="summary-subtotal">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-sm text-red-600">
                                <span>Diskon</span>
                                <span id="summary-disc">- Rp 0</span>
                            </div>
                            <div id="summary-taxes" class="space-y-1 text-sm text-gray-600">
                                {{-- Tax items injected by JS --}}
                            </div>
                            
                            <div class="pt-4 mt-2 border-t border-gray-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-bold text-gray-900">TOTAL BARU</span>
                                    <span class="text-xl font-bold text-indigo-600" id="summary-grand">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        {{-- OPSI KELEBIHAN BAYAR --}}
                        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-100">
                            <label class="block text-xs font-bold text-yellow-800 uppercase mb-2">Jika terjadi kelebihan bayar:</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <label for="overpayment_deposit" class="ml-2 block text-sm text-gray-700">
                                        Simpan ke <strong>Saldo Kredit</strong>
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="overpayment_action" id="overpayment_refund" value="refund" class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <label for="overpayment_refund" class="ml-2 block text-sm text-gray-700">
                                        Proses <strong>Refund Manual</strong>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="grid grid-cols-1 gap-3 pt-2">
                            <button type="submit" class="w-full py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center transition">
                                <i class="bi bi-check-circle mr-2"></i> Simpan Koreksi
                            </button>
                            <a href="{{ route('invoice-adjustments.create') }}" class="w-full py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center transition">
                                Batal
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- TEMPLATE ROW (Hidden) --}}
<template id="product-row-template">
    <tr class="hover:bg-gray-50 transition group text-sm">
        <td class="px-3 py-3 text-center align-middle">
            <input type="checkbox" class="row-select rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 cursor-pointer">
        </td>
        
        <td class="px-3 py-3 align-middle">
            {{-- Select2 akan otomatis mengikuti lebar kolom ini --}}
            <select class="product-select form-select block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                <option value="" data-price="0" disabled selected>-- Cari Produk --</option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">{{ $product->product_name }}</option>
                @endforeach
            </select>
        </td>
        
        <td class="px-3 py-3 align-middle">
            <input type="number" class="quantity form-input block w-full rounded-md border-gray-300 text-center font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-1" value="1" min="1" required>
        </td>
        
        <td class="px-3 py-3 align-middle">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2">
                    <span class="text-gray-500 text-xs font-medium">Rp</span>
                </div>
                <input type="text" class="purchase-price-formatted form-input block w-full rounded-md border-gray-300 pl-8 text-right shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium" placeholder="0">
            </div>
            <input type="hidden" class="purchase-price-hidden" value="0">
            
            <div class="flex items-center mt-1.5 justify-end">
                <input type="checkbox" value="1" class="update-master-price h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label class="ml-1.5 text-[11px] text-gray-500 cursor-pointer hover:text-indigo-600 transition">Update Master</label>
            </div>
        </td>
        
        <td class="px-3 py-3 align-middle text-right font-bold text-gray-800">
            <span class="subtotal">Rp 0</span>
        </td>
        
        <td class="px-3 py-3 text-center align-middle">
            <button type="button" class="remove-product-btn text-gray-400 hover:text-red-600 transition bg-white hover:bg-red-50 rounded-lg p-1.5 border border-transparent hover:border-red-200">
                <i class="bi bi-trash text-lg"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- VARIABEL & ELEMENT ---
    const existingItems = @json($invoice->items);
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const addProductBtn = document.getElementById('add-product-btn');
    const taxOptionsContainer = document.getElementById('tax-options');
    const discountInput = document.getElementById('discount_percentage');
    const headerRowSelect = document.getElementById('header-row-select');

    let productIndex = 0;
    const autoNumericInstances = new Map();

    // --- HELPER FUNCTIONS ---
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }
    
    // --- FUNGSI HITUNG TOTAL ---
    function calculateTotals() {
        let subtotalProducts = 0;
        
        // Loop setiap baris produk
        const rows = Array.from(productItemsContainer.querySelectorAll('tr'));
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.purchase-price-hidden').value) || 0;
            const subtotal = qty * price;

            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            subtotalProducts += subtotal;
        });

        // Hitung Diskon
        const discountRate = parseFloat(discountInput.value) || 0;
        const discountAmount = subtotalProducts * (discountRate / 100);
        const subtotalAfterDiscount = subtotalProducts - discountAmount;

        // Hitung Pajak
        let totalTaxAmount = 0;
        let taxHtml = '';
        document.querySelectorAll('.tax-checkbox:checked').forEach(cb => {
            const rate = parseFloat(cb.dataset.rate) || 0;
            // const name = cb.nextElementSibling.innerText.trim(); // Menggunakan innerText dari label
            // Mengambil text label dengan cara yang lebih aman untuk Tailwind structure
            const labelText = document.querySelector(`label[for="${cb.id}"]`).innerText;
            const taxVal = subtotalAfterDiscount * (rate / 100);
            totalTaxAmount += taxVal;
            
            taxHtml += `
                <div class="flex justify-content-between justify-between">
                    <span>+ ${labelText}</span>
                    <span>${formatRupiah(taxVal)}</span>
                </div>`;
        });

        const grandTotal = subtotalAfterDiscount + totalTaxAmount;

        // Update Tampilan Summary
        document.getElementById('summary-subtotal').textContent = formatRupiah(subtotalProducts);
        document.getElementById('summary-disc').textContent = `(-) ${formatRupiah(discountAmount)}`;
        document.getElementById('summary-taxes').innerHTML = taxHtml;
        document.getElementById('summary-grand').textContent = formatRupiah(grandTotal);
    }

    // --- FUNGSI TAMBAH BARIS ---
    function addProductRow(item = null) {
        const clone = productRowTemplate.content.cloneNode(true);
        const row = clone.querySelector('tr');
        const currentIndex = productIndex;
        
        // Elements
        const select = row.querySelector('.product-select');
        const qtyInput = row.querySelector('.quantity');
        const priceDisplay = row.querySelector('.purchase-price-formatted');
        const priceHidden = row.querySelector('.purchase-price-hidden');
        const updateCheck = row.querySelector('.update-master-price');
        const removeBtn = row.querySelector('.remove-product-btn');

        // Attributes Name
        select.name = `products[${currentIndex}][product_id]`;
        qtyInput.name = `products[${currentIndex}][quantity]`;
        priceHidden.name = `products[${currentIndex}][price]`;
        updateCheck.name = `products[${currentIndex}][update_master_price]`;

        productItemsContainer.appendChild(row);

        // Init Select2
        const select2 = $(select).select2({
            theme: 'bootstrap-5',
            placeholder: '-- Cari Produk --',
            dropdownParent: $(select).parent(), // Penting agar dropdown nempel di input
            width: '100%'
        });

        // Init AutoNumeric
        const anInstance = new AutoNumeric(priceDisplay, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0'
        });
        autoNumericInstances.set(currentIndex, anInstance);

        // Events
        select2.on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const defaultPrice = parseFloat(selectedOption.dataset.price) || 0;
            
            if (!item || $(this).val() != item.product_id) {
                anInstance.set(defaultPrice);
                priceHidden.value = defaultPrice;
                calculateTotals();
            }
        });

        priceDisplay.addEventListener('autoNumeric:rawValueModified', e => {
            priceHidden.value = e.detail.newRawValue;
            calculateTotals();
        });

        qtyInput.addEventListener('input', calculateTotals);

        removeBtn.addEventListener('click', function() {
            // Hapus Select2 agar memory bersih
            select2.select2('destroy');
            autoNumericInstances.delete(currentIndex);
            row.remove();
            calculateTotals();
        });

        // Populate Data (Edit Mode)
        if (item) {
            $(select).val(item.product_id).trigger('change');
            qtyInput.value = item.quantity;
            
            setTimeout(() => {
                anInstance.set(item.price_per_unit);
                priceHidden.value = item.price_per_unit;
                calculateTotals();
            }, 50);
        }

        productIndex++;
    }

    // --- EVENT LISTENERS GLOBAL ---
    addProductBtn.addEventListener('click', () => addProductRow());
    
    if(taxOptionsContainer) {
        taxOptionsContainer.addEventListener('change', (e) => {
            if(e.target.classList.contains('tax-checkbox')) calculateTotals();
        });
    }
    
    discountInput.addEventListener('input', calculateTotals);

    if(headerRowSelect) {
        headerRowSelect.addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(cb => cb.checked = this.checked);
        });
    }

    // --- INITIAL LOAD ---
    if (existingItems.length > 0) {
        existingItems.forEach(item => addProductRow(item));
    } else {
        addProductRow();
    }
});
</script>
@endpush
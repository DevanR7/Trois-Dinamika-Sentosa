@extends('admin.layouts.app')

@section('title', 'Buat Pesanan Penjualan')

@push('styles')
    <style>
        /* Styling agar tinggi input Select2 konsisten dengan input lain */
        .select2-container .select2-selection--single { height: 42px !important; display: flex; align-items: center; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 42px !important; padding-left: 12px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; }
    </style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto pb-20 animate-enter">
    
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <nav class="flex text-sm text-slate-500 mb-1">
                <a href="{{ route('admin.sales-orders.index') }}" class="hover:text-indigo-600 transition-colors">Pesanan</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Baru</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Buat Pesanan Penjualan</h1>
        </div>
        <a href="{{ route('admin.sales-orders.index') }}" class="h-[48px] px-6 bg-white border border-slate-300 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 shadow-sm">
            <i class="material-icons text-[18px]">arrow_back</i> Kembali
        </a>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
            <div class="flex items-start gap-3">
                <i class="material-icons text-red-600 text-xl mt-0.5">error_outline</i>
                <div>
                    <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                    <ul class="mt-1 list-disc list-inside text-xs text-red-600">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.sales-orders.store') }}" method="POST" id="sales-order-form">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            {{-- KOLOM KIRI: INFO & ITEM (Span 8) --}}
            <div class="lg:col-span-8 space-y-6">
                
                {{-- CARD 1: INFO PESANAN --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                            <i class="material-icons text-[20px]">info</i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informasi Pesanan</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="client_id" class="block text-xs font-bold text-slate-500 uppercase mb-1">Pelanggan (Klien) <span class="text-red-500">*</span></label>
                            
                            {{-- FIXED: Hapus 'required' dari HTML select, kita validasi via JS --}}
                            <select name="client_id" id="client_id" class="form-input so-select2" style="width: 100%">
                                <option value="" disabled selected>-- Pilih Klien --</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}" @selected(old('client_id') == $client->client_id)>{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="order_date" class="block text-xs font-bold text-slate-500 uppercase mb-1">Tanggal Pesanan</label>
                            <input type="date" class="form-input" id="order_date" name="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div>
                            <label for="sales_id" class="block text-xs font-bold text-slate-500 uppercase mb-1">Sales Person</label>
                            <select name="sales_id" id="sales_id" class="form-input so-select2" style="width: 100%">
                                <option value="" selected>-- Pilih Sales --</option>
                                @foreach ($salesUsers as $sale)
                                    <option value="{{ $sale->user_id }}" @selected(old('sales_id') == $sale->user_id)>{{ $sale->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: RINCIAN ITEM --}}
                <div class="dashboard-card p-0 overflow-hidden shadow-sm min-h-[300px]">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide flex items-center gap-2">
                            <i class="material-icons text-indigo-600 text-[20px]">shopping_cart</i> Rincian Item
                        </h3>
                        <button type="button" id="add-product-btn" class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition shadow-sm gap-1">
                            <i class="material-icons text-base">add</i> Tambah Item
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="dashboard-table min-w-full">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase w-5/12 pl-6">Produk</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-center w-2/12">Qty</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right w-3/12">Harga (@)</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right w-2/12">Subtotal</th>
                                    <th class="px-4 py-3 w-10 pr-6"></th>
                                </tr>
                            </thead>
                            <tbody id="product-items" class="divide-y divide-slate-100 bg-white">
                                {{-- JS Inject Rows Here --}}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="p-6 border-t border-slate-100 bg-slate-50/30">
                        <label for="notes" class="block text-xs font-bold text-slate-500 uppercase mb-1">Catatan / Instruksi</label>
                        <textarea class="form-textarea bg-white w-full" name="notes" id="notes" rows="2" placeholder="Contoh: Kirim sebelum jam 5 sore...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: TOTAL & AKSI (Span 4) --}}
            <div class="lg:col-span-4 space-y-6">
                <div class="dashboard-card p-6 shadow-lg sticky top-6 border-t-4 border-indigo-500">
                    <h3 class="card-title mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="material-icons text-indigo-600">calculate</i> Ringkasan
                    </h3>

                    <div class="flex justify-between items-center mb-8 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Tagihan</span>
                        <span class="text-2xl font-bold text-indigo-700 font-mono tracking-tight" id="grand-total">Rp 0</span>
                    </div>

                    <div class="space-y-3">
                        <button type="submit" id="submit-btn" class="w-full h-[48px] bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-md transition-all flex justify-center items-center gap-2 hover:-translate-y-0.5">
                            <i class="material-icons text-[18px]">save</i> Simpan Pesanan
                        </button>
                        <a href="{{ route('admin.sales-orders.index') }}" class="w-full h-[48px] bg-white border border-slate-300 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-50 transition-all text-center shadow-sm flex items-center justify-center">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE ROW --}}
<template id="product-row-template">
    <tr class="hover:bg-slate-50/50 transition-colors group">
        <td class="p-3 pl-6 align-top">
            {{-- FIXED: Hapus 'required' di sini juga --}}
            <select class="product-select form-input w-full text-sm" style="width: 100%">
                <option></option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">
                        {{ $product->product_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="p-3 align-top">
            <input type="number" class="form-input quantity text-center w-full font-bold text-slate-700 h-10" value="1" min="1" required>
        </td>
        <td class="p-3 align-top">
            <div class="relative">
                <input type="text" class="form-input price-display block w-full text-right text-sm bg-slate-50 text-slate-500 border-slate-200 cursor-not-allowed font-mono" readonly>
            </div>
            <input type="hidden" class="price-raw">
        </td>
        <td class="p-3 align-top text-right font-bold text-slate-800 text-sm align-middle font-mono">
            <span class="subtotal">Rp 0</span>
        </td>
        <td class="p-3 pr-6 align-top text-center align-middle">
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition remove-product-btn">
                <i class="material-icons text-[18px]">delete</i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. INIT SELECT2 HEADER
    $('.so-select2').select2({ 
        placeholder: '-- Pilih --', 
        width: '100%', 
        dropdownCssClass: 'select2-dropdown-clean', 
        allowClear: true 
    });

    const form = document.getElementById('sales-order-form');
    const productItemsContainer = document.getElementById('product-items');
    const productRowTemplate = document.getElementById('product-row-template');
    const addProductBtn = document.getElementById('add-product-btn');
    let productIndex = 0;

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // 2. KALKULASI
    function calculateTotals() {
        let grandTotal = 0;
        productItemsContainer.querySelectorAll('tr').forEach(row => {
            const price = parseFloat(row.querySelector('.price-raw').value) || 0;
            const quantity = parseInt(row.querySelector('.quantity').value) || 0;
            const subtotal = price * quantity;
            row.querySelector('.subtotal').textContent = formatRupiah(subtotal);
            grandTotal += subtotal;
        });
        document.getElementById('grand-total').textContent = formatRupiah(grandTotal);
    }

    // 3. TAMBAH BARIS
    function addProductRow(item = null) {
        const clone = productRowTemplate.content.cloneNode(true);
        const tr = clone.querySelector('tr');

        const productSelect = tr.querySelector('.product-select');
        const quantityInput = tr.querySelector('.quantity');
        const priceDisplay = tr.querySelector('.price-display');
        const priceRaw = tr.querySelector('.price-raw');
        const removeBtn = tr.querySelector('.remove-product-btn');

        // Naming for Laravel
        productSelect.name = `products[${productIndex}][product_id]`;
        quantityInput.name = `products[${productIndex}][quantity]`;
        priceRaw.name = `products[${productIndex}][price]`; 

        productItemsContainer.appendChild(tr);

        // FIXED: Hapus dropdownParent agar render di body (menghindari masalah clipping & focus trap)
        const select2 = $(productSelect).select2({
            placeholder: 'Pilih Produk...',
            dropdownCssClass: 'select2-dropdown-clean',
            // dropdownParent: $(productSelect).parent(), // <--- BARIS INI DIHAPUS
            width: '100%'
        });

        const anPrice = new AutoNumeric(priceDisplay, {
            currencySymbol: 'Rp ',
            decimalCharacter: ',',
            digitGroupSeparator: '.',
            decimalPlaces: 0,
            minimumValue: '0',
            readOnly: true 
        });

        // Event: Saat Produk Dipilih
        select2.on('select2:select', function(e) {
            const selectedOption = e.params.data.element;
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            
            anPrice.set(price);
            priceRaw.value = price;
            calculateTotals();
        });

        quantityInput.addEventListener('input', calculateTotals);
        
        removeBtn.addEventListener('click', () => {
            select2.select2('destroy');
            tr.remove();
            calculateTotals();
        });

        productIndex++;
    }

    addProductBtn.addEventListener('click', () => addProductRow());
    
    // Tambah 1 baris kosong saat load
    addProductRow();
    
    // 4. VALIDASI MANUAL SEBELUM SUBMIT
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Stop dulu

        // Validasi Klien
        const clientSelect = $('#client_id');
        if (!clientSelect.val()) {
            Swal.fire('Error', 'Silakan pilih Pelanggan (Klien).', 'error');
            return;
        }

        // Validasi Produk
        const rows = productItemsContainer.querySelectorAll('tr');
        if (rows.length === 0) {
            Swal.fire('Error', 'Harap tambahkan minimal satu produk.', 'warning');
            return;
        }

        let isValid = true;
        rows.forEach((row, index) => {
            const select = $(row.querySelector('.product-select'));
            if (!select.val()) {
                isValid = false;
                // Highlight error (opsional)
            }
        });

        if (!isValid) {
            Swal.fire('Error', 'Pastikan semua baris produk sudah dipilih.', 'warning');
            return;
        }

        // Jika lolos, submit form
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="material-icons animate-spin text-sm">sync</i> Menyimpan...';
        
        this.submit();
    });

    @if(session('success')) window.showToast("{{ session('success') }}", 'success'); @endif
    @if(session('error')) window.showToast("{{ session('error') }}", 'error'); @endif
});
</script>
@endpush
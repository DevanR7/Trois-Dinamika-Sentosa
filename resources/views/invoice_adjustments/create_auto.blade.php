@extends('layouts.app')

@section('title', 'Koreksi Otomatis Invoice')

@section('content')
<div class="max-w-full mx-auto animate-enter pb-10">
    
    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <a href="{{ route('invoices.index') }}" class="hover:text-indigo-600 transition">Invoice</a>
                <i class="material-icons text-[12px]">chevron_right</i>
                <span class="text-slate-600">Koreksi Item</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Koreksi Otomatis</h2>
            <p class="text-sm text-slate-500 mt-1">
                Revisi untuk Invoice: <span class="font-mono font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ $invoice->invoice_number }}</span>
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('invoice-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg font-bold text-slate-600 hover:bg-slate-50 shadow-sm transition text-sm">
                <i class="material-icons text-base mr-2">arrow_back</i> Kembali
            </a>
        </div>
    </div>

    {{-- ALERT ERROR --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="material-icons text-red-500 mt-0.5">error</i>
            <div>
                <h3 class="text-sm font-bold text-red-800">Terdapat kesalahan input</h3>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- INFO BOX --}}
    <div class="mb-6 bg-blue-50/50 border border-blue-100 rounded-xl p-4 flex items-start gap-3 shadow-sm">
        <i class="material-icons text-blue-500 mt-0.5">info</i>
        <div>
            <h3 class="text-sm font-bold text-blue-800">Mode Revisi Item</h3>
            <p class="text-sm text-blue-600/80 mt-1 leading-relaxed">
                Silakan ubah data (Qty/Harga) di bawah ini sesuai kondisi riil yang seharusnya. Sistem akan otomatis menghitung selisihnya (Nota Debet/Kredit) tanpa mengubah data historis invoice asli.
            </p>
        </div>
    </div>

    <form action="{{ route('invoice-adjustments.store.auto', $invoice->invoice_id) }}" method="POST" id="adjustment-form">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            
            {{-- KOLOM KIRI: FORM ITEM & DETAIL --}}
            <div class="xl:col-span-2 space-y-6">
                
                {{-- 1. INFORMASI READONLY --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-sm">description</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Informasi Dasar (Read-only)</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Klien</label>
                            <input type="text" class="form-input bg-slate-50 cursor-not-allowed text-slate-500" value="{{ $invoice->client->client_name }}" readonly>
                            <input type="hidden" name="client_id" value="{{ $invoice->client_id }}">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Tanggal Invoice</label>
                            <input type="date" class="form-input bg-slate-50 cursor-not-allowed text-slate-500" value="{{ optional($invoice->order_date)->format('Y-m-d') }}" readonly>
                            <input type="hidden" name="order_date" value="{{ optional($invoice->order_date)->format('Y-m-d') }}">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Sales</label>
                            <input type="text" class="form-input bg-slate-50 cursor-not-allowed text-slate-500" value="{{ $invoice->sales->full_name ?? '-- Tanpa Sales --' }}" readonly>
                            <input type="hidden" name="user_id_sales" value="{{ $invoice->user_id_sales }}">
                            <input type="hidden" name="due_date" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
                        </div>

                    </div>
                </div>

                {{-- 2. TABEL ITEM --}}
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-white">
                        <div class="flex items-center gap-2">
                            <i class="material-icons text-indigo-500 text-sm">inventory_2</i>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Revisi Item</h3>
                        </div>
                        <button type="button" id="add-product-btn" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                            <i class="material-icons text-sm mr-1">add</i> Tambah Item
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th class="w-10 text-center">
                                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" id="header-row-select">
                                    </th>
                                    <th class="min-w-[250px]">Produk</th>
                                    <th class="w-24 text-center">Qty</th>
                                    <th class="w-48 text-right">Harga Revisi (@)</th>
                                    <th class="w-40 text-right">Subtotal</th>
                                    <th class="w-12 text-center"><i class="material-icons text-slate-400 text-sm">settings</i></th>
                                </tr>
                            </thead>
                            <tbody id="product-items">
                                {{-- JS akan mengisi row di sini --}}
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50/50">
                        <label for="reason" class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Alasan Koreksi <span class="text-red-500">*</span></label>
                        <textarea name="reason" id="reason" class="form-textarea" placeholder="Contoh: Koreksi salah input harga, perubahan qty, pengembalian barang..." required>{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: KALKULASI --}}
            <div class="xl:col-span-1">
                <div class="dashboard-card p-0 sticky top-6">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                        <i class="material-icons text-slate-400 text-sm">calculate</i>
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Kalkulasi Revisi</h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        
                        {{-- OPSI KALKULASI --}}
                        <div>
                            <label for="discount_percentage" class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Diskon Global (%)</label>
                            <div class="relative">
                                <input type="number" step="any" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $invoice->discount_percentage) }}" min="0" max="100" class="form-input pr-8">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-slate-400 font-bold text-xs">%</span>
                                </div>
                            </div>
                        </div>

                        <div id="tax-options">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2 ml-1">Pajak</label>
                            <div class="space-y-2 bg-white p-3 rounded-lg border border-slate-100">
                                @foreach(\App\Models\Tax::where('is_active', true)->get() as $tax)
                                    <div class="flex items-center">
                                        <input type="checkbox" name="taxes[]" value="{{ $tax->id }}" id="tax{{ $tax->id }}" data-rate="{{ $tax->rate }}" @checked($invoice->taxes->contains($tax->id)) class="tax-checkbox h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                        <label for="tax{{ $tax->id }}" class="ml-2 block text-sm text-slate-600 cursor-pointer select-none">
                                            {{ $tax->name }} ({{ $tax->rate }}%)
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="h-px bg-slate-200 border-none my-4"></div>

                        {{-- SUMMARY BOX --}}
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm text-slate-500">
                                <span>Subtotal Barang</span>
                                <span class="font-bold text-slate-700" id="summary-subtotal">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-sm text-red-500">
                                <span>Diskon</span>
                                <span class="font-bold" id="summary-disc">- Rp 0</span>
                            </div>
                            <div id="summary-taxes" class="space-y-1 text-sm text-slate-500">
                                {{-- Tax items injected by JS --}}
                            </div>
                            
                            <div class="pt-4 mt-2 border-t border-slate-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-bold text-slate-800">TOTAL BARU</span>
                                    <span class="text-xl font-bold text-indigo-600" id="summary-grand">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        {{-- OPSI KELEBIHAN BAYAR --}}
                        <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                            <label class="block text-[10px] font-bold text-amber-700 uppercase mb-2 tracking-wide">Jika terjadi kelebihan bayar:</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked class="h-4 w-4 border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <label for="overpayment_deposit" class="ml-2 block text-xs font-bold text-amber-800">
                                        Simpan ke Saldo Kredit
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="overpayment_action" id="overpayment_refund" value="refund" class="h-4 w-4 border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <label for="overpayment_refund" class="ml-2 block text-xs font-bold text-amber-800">
                                        Proses Refund Manual
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- ACTION BUTTONS --}}
                        <div class="grid grid-cols-1 gap-3 pt-2">
                            <button type="submit" class="w-full py-3 px-4 rounded-lg shadow-md text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center transition-all">
                                <i class="material-icons text-sm mr-2">check_circle</i> Simpan Koreksi
                            </button>
                            <a href="{{ route('invoice-adjustments.create') }}" class="w-full py-3 px-4 border border-slate-300 rounded-lg text-sm font-bold text-slate-600 bg-white hover:bg-slate-50 focus:outline-none text-center transition-all">
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
    <tr class="group transition-colors">
        <td class="text-center">
            <input type="checkbox" class="row-select rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
        </td>
        
        <td>
            {{-- Select2 akan otomatis mengikuti lebar kolom ini --}}
            <select class="product-select form-select w-full" required>
                <option value="" data-price="0" disabled selected></option>
                @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">{{ $product->product_name }}</option>
                @endforeach
            </select>
        </td>
        
        <td>
            <input type="number" class="quantity form-input text-center font-bold px-1" value="1" min="1" required>
        </td>
        
        <td>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <span class="text-slate-400 text-xs font-bold">Rp</span>
                </div>
                <input type="text" class="purchase-price-formatted form-input pl-9 text-right font-medium" placeholder="0">
            </div>
            <input type="hidden" class="purchase-price-hidden" value="0">
            
            <div class="flex items-center mt-1.5 justify-end">
                <input type="checkbox" value="1" class="update-master-price h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                <label class="ml-1.5 text-[10px] font-bold text-slate-500 cursor-pointer hover:text-indigo-600 transition">Update Master</label>
            </div>
        </td>
        
        <td class="text-right">
            <span class="subtotal font-bold text-slate-800">Rp 0</span>
        </td>
        
        <td class="text-center">
            <button type="button" class="remove-product-btn text-slate-400 hover:text-red-600 transition-colors p-1.5 rounded-full hover:bg-red-50">
                <i class="material-icons text-lg">delete</i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- Kita tidak perlu load jquery/select2/autonumeric lagi jika sudah ada di app.js, 
     tapi untuk keamanan jika struktur Anda membutuhkan load manual per page, saya biarkan CDN-nya 
     TAPI sebaiknya gunakan window.AutoNumeric dari app.js jika sudah tersedia --}}

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
            // Ambil label text dengan aman
            const labelElement = document.querySelector(`label[for="${cb.id}"]`);
            const labelText = labelElement ? labelElement.innerText : 'Tax';
            
            const taxVal = subtotalAfterDiscount * (rate / 100);
            totalTaxAmount += taxVal;
            
            taxHtml += `
                <div class="flex justify-between text-slate-600">
                    <span>+ ${labelText}</span>
                    <span class="font-medium">${formatRupiah(taxVal)}</span>
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
            dropdownParent: $(select).parent(), 
            width: '100%',
            dropdownCssClass: 'select2-dropdown-clean'
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
            Swal.fire({
                title: 'Hapus Item?',
                text: "Item ini akan dihapus dari daftar koreksi.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    popup: 'bg-white rounded-xl border border-slate-100 shadow-xl p-6',
                    confirmButton: 'px-4 py-2 rounded-lg font-bold',
                    cancelButton: 'px-4 py-2 rounded-lg font-bold hover:bg-slate-100 text-slate-600'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    select2.select2('destroy');
                    autoNumericInstances.delete(currentIndex);
                    row.remove();
                    calculateTotals();
                }
            });
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
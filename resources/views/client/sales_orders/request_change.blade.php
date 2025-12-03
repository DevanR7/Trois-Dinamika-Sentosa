@extends('client.layouts.app')

@section('title', 'Ajukan Perubahan')

@push('styles')
    {{-- Load Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Styling khusus agar tinggi input Select2 konsisten */
        .select2-container .select2-selection--single { height: 40px !important; display: flex; align-items: center; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px !important; padding-left: 12px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px !important; }
    </style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-20 animate-enter">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('client.sales-orders.show', $order->order_id) }}" class="flex items-center text-slate-500 hover:text-slate-800 transition text-sm font-medium">
            <i class="material-icons text-[18px] mr-1">arrow_back</i> Kembali ke Detail
        </a>
    </div>

    <div class="dashboard-card p-0 overflow-hidden shadow-sm border border-slate-200 rounded-xl bg-white">
        <div class="bg-indigo-600 px-6 py-4 border-b border-indigo-700">
            <h2 class="text-white font-bold text-lg flex items-center gap-2">
                <i class="material-icons">edit_note</i>
                Ajukan Perubahan Pesanan: {{ $order->order_number }}
            </h2>
        </div>
        
        <div class="p-6">
            {{-- Error Handling --}}
            @if ($errors->any())
                <div class="mb-6 bg-red-50 text-red-700 p-4 rounded-lg border border-red-200 shadow-sm">
                    <div class="flex items-start gap-2">
                        <i class="material-icons text-red-500">error</i>
                        <ul class="list-disc list-inside text-sm mt-0.5">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('client.sales-orders.requestChange.store', $order->order_id) }}" method="POST" id="change-request-form">
                @csrf

                {{-- 1. PILIHAN JENIS PERMINTAAN (Card Style) --}}
                <div class="mb-8">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Jenis Permintaan <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Option: Cancel --}}
                        <label class="cursor-pointer relative group">
                            <input type="radio" name="request_type" value="cancel" class="peer sr-only" {{ old('request_type') == 'cancel' ? 'checked' : '' }} required>
                            <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-red-400 peer-checked:border-red-500 peer-checked:bg-red-50 transition-all shadow-sm h-full">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                                        <i class="material-icons">cancel</i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-700 group-hover:text-red-700 transition-colors">Batalkan Pesanan</h6>
                                        <p class="text-xs text-slate-500">Batalkan seluruh pesanan ini.</p>
                                    </div>
                                    <i class="material-icons ml-auto text-red-500 opacity-0 peer-checked:opacity-100 transition-all transform scale-0 peer-checked:scale-100">check_circle</i>
                                </div>
                            </div>
                        </label>

                        {{-- Option: Modify --}}
                        <label class="cursor-pointer relative group">
                            <input type="radio" name="request_type" value="modify" class="peer sr-only" {{ old('request_type', 'modify') == 'modify' ? 'checked' : '' }} required>
                            <div class="p-4 rounded-xl border-2 border-slate-200 hover:border-indigo-400 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition-all shadow-sm h-full">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                        <i class="material-icons">edit</i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">Ubah Item/Jumlah</h6>
                                        <p class="text-xs text-slate-500">Tambah, kurang, atau hapus item.</p>
                                    </div>
                                    <i class="material-icons ml-auto text-indigo-500 opacity-0 peer-checked:opacity-100 transition-all transform scale-0 peer-checked:scale-100">check_circle</i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- 2. CATATAN KLIEN --}}
                <div class="mb-8">
                    <label for="client_notes" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Alasan / Catatan</label>
                    <textarea name="client_notes" id="client_notes" class="form-textarea w-full border-slate-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition shadow-sm" rows="3" placeholder="Jelaskan alasan pembatalan atau detail perubahan..." required>{{ old('client_notes') }}</textarea>
                </div>

                {{-- 3. BAGIAN UBAH ITEM --}}
                <div id="modify-items-section" class="mb-6 {{ old('request_type', 'modify') == 'modify' ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Daftar Perubahan Item</label>
                        <button type="button" id="add-item-btn" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 bg-indigo-50 px-3 py-1.5 rounded-lg transition">
                            <i class="material-icons text-[16px]">add_circle</i> Tambah Produk
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-xs">
                                <tr>
                                    <th class="p-3 pl-4 w-5/12">Produk</th>
                                    <th class="p-3 text-center w-24">Asli</th>
                                    <th class="p-3 text-center w-32">Baru</th>
                                    <th class="p-3 text-center w-16">Hapus</th>
                                </tr>
                            </thead>
                            <tbody id="request-items-container" class="divide-y divide-slate-100 bg-white">
                                {{-- JS Injected Here --}}
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Pesan error validasi JS --}}
                    <div id="items-validation-error" class="text-red-500 text-xs mt-2 hidden flex items-center gap-1 bg-red-50 p-2 rounded border border-red-100">
                        <i class="material-icons text-[14px]">error</i> <span>Pastikan item valid.</span>
                    </div>
                </div>

                <div class="border-t border-slate-100 mt-8 pt-6 flex justify-end gap-3">
                    <a href="{{ route('client.sales-orders.show', $order->order_id) }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition shadow-sm">Batal</a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-md transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="material-icons text-sm">send</i> Kirim Permintaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- TEMPLATE UNTUK BARIS ITEM (Hidden) --}}
<template id="item-row-template">
    <tr class="group hover:bg-slate-50 transition-colors">
        <td class="p-3 pl-4">
            {{-- CLASS KHUSUS: 'request-change-select' --}}
            <select class="request-change-select w-full text-sm" disabled style="width: 100%;">
                <option value="">-- Pilih Produk --</option>
                 @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">{{ $product->product_name }}</option>
                @endforeach
            </select>
            <input type="hidden" class="product-id-hidden">
            <input type="hidden" class="original-quantity-hidden">
            <input type="hidden" class="action-hidden">
        </td>
        <td class="p-3 text-center text-slate-500 font-mono original-quantity-display bg-slate-50/50">-</td>
        <td class="p-3 text-center">
        <input type="number" 
           class="form-input text-center h-9 requested-quantity w-20 mx-auto border-slate-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition" 
           value="1" 
           min="0" 
           step="0.01">
        </td>
        <td class="p-3 text-center">
            <button type="button" class="remove-item-btn text-slate-400 hover:text-red-500 transition p-1.5 rounded-full hover:bg-red-50">
                <i class="material-icons text-[20px]">delete</i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- Load Scripts Manual untuk menghindari konflik --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('change-request-form');
    const requestTypeRadios = form.querySelectorAll('input[name="request_type"]');
    const modifySection = document.getElementById('modify-items-section');
    const itemsContainer = document.getElementById('request-items-container');
    const itemRowTemplate = document.getElementById('item-row-template');
    const addItemBtn = document.getElementById('add-item-btn');
    const submitBtn = form.querySelector('button[type="submit"]');
    const itemsValidationError = document.getElementById('items-validation-error');

    const originalOrderItems = @json($order->items->keyBy('product_id'));
    let itemIndex = 0;

    // --- 1. VALIDASI FORM ---
    function validateForm() {
        let isValid = true;
        itemsValidationError.classList.add('hidden'); 

        if (form.querySelector('input[name="request_type"]:checked').value === 'modify') {
            const itemRows = itemsContainer.querySelectorAll('tr');
            
            // Cek minimal 1 item
            if (itemRows.length === 0) {
                 isValid = false;
                 itemsValidationError.querySelector('span').textContent = 'Harap tambahkan setidaknya satu item.';
                 itemsValidationError.classList.remove('hidden');
            } else {
                 // Cek kelengkapan setiap baris
                 itemRows.forEach(row => {
                     const action = row.querySelector('.action-hidden').value;
                     const productId = row.querySelector('.product-id-hidden').value;
                     
                     if (action === 'add' && !productId) {
                         isValid = false;
                         itemsValidationError.querySelector('span').textContent = 'Semua item baru harus dipilih produknya.';
                         itemsValidationError.classList.remove('hidden');
                     }
                 });
            }
        }
        
        submitBtn.disabled = !isValid;
        if (!isValid) {
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.remove('hover:bg-indigo-700', 'shadow-md', 'transform');
        } else {
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.add('hover:bg-indigo-700', 'shadow-md', 'transform');
        }
    }

    // --- 2. TOGGLE SECTION ---
    function toggleModifySection() {
        if (form.querySelector('input[name="request_type"]:checked').value === 'modify') {
            modifySection.classList.remove('hidden');
            // Load original items only once
            if (itemsContainer.children.length === 0) {
                loadOriginalItems();
            }
        } else {
            modifySection.classList.add('hidden');
        }
        validateForm();
    }

    // --- 3. ADD ITEM ROW ---
    function addRequestItemRow(itemData = null) {
        const templateClone = itemRowTemplate.content.cloneNode(true);
        const newRow = templateClone.querySelector('tr');
        
        const productSelect = newRow.querySelector('.request-change-select');
        const quantityInput = newRow.querySelector('.requested-quantity');
        const originalQtyDisplay = newRow.querySelector('.original-quantity-display');
        const removeBtn = newRow.querySelector('.remove-item-btn');
        
        const productIdHidden = newRow.querySelector('.product-id-hidden');
        const originalQtyHidden = newRow.querySelector('.original-quantity-hidden');
        const actionHidden = newRow.querySelector('.action-hidden');

        // Naming for Laravel Array Validation
        productIdHidden.name = `items[${itemIndex}][product_id]`;
        quantityInput.name = `items[${itemIndex}][quantity]`;
        originalQtyHidden.name = `items[${itemIndex}][original_quantity]`;
        actionHidden.name = `items[${itemIndex}][action]`;

        // Populate Data
        if (itemData) {
            // EXISTING ITEM
            productSelect.value = itemData.product_id;
            productSelect.disabled = true; // Existing items cannot change product, only qty
            originalQtyDisplay.textContent = itemData.quantity;
            quantityInput.value = itemData.quantity;
            
            productIdHidden.value = itemData.product_id;
            originalQtyHidden.value = itemData.quantity;
            actionHidden.value = 'update_qty'; // Default action
        } else {
            // NEW ITEM
             productSelect.disabled = false;
             originalQtyDisplay.textContent = '-';
             quantityInput.value = 1;
             
             productIdHidden.value = '';
             originalQtyHidden.value = 0;
             actionHidden.value = 'add';
        }

        itemsContainer.appendChild(newRow);

        // --- INIT SELECT2 (FIXED: No dropdownParent) ---
        // Hanya init select2 jika dropdown enable (untuk item baru)
        // Item lama biarkan disabled select biasa (tidak perlu select2)
        if (!productSelect.disabled) {
   $(productSelect).select2({
         placeholder: '-- Pilih Produk --',
         width: '100%',
         dropdownCssClass: 'select2-dropdown-clean', // Gunakan class custom
         // theme: 'bootstrap-5', // HAPUS INI
         // dropdownParent: ... // HAPUS INI
    }).on('change', function() {
         productIdHidden.value = $(this).val();
         validateForm();
    });
}

        // --- EVENTS ---
        
        // Qty Change Logic
        quantityInput.addEventListener('input', function() {
             const requestedQty = parseInt(this.value) || 0;
             const originalQty = parseInt(originalQtyHidden.value) || 0;

            if (actionHidden.value !== 'add') {
                // Logic untuk item existing
                 if (requestedQty === 0) {
                     actionHidden.value = 'remove';
                     newRow.classList.add('bg-red-50'); // Visual feedback item dihapus
                     quantityInput.classList.add('text-red-600', 'font-bold');
                 } else { 
                     actionHidden.value = 'update_qty';
                     newRow.classList.remove('bg-red-50');
                     quantityInput.classList.remove('text-red-600', 'font-bold');
                 }
            } else {
                // Logic untuk item baru
                 if (requestedQty === 0) {
                     // Jika qty 0 untuk item baru, hapus barisnya
                     if(!productSelect.disabled) $(productSelect).select2('destroy');
                     newRow.remove();
                     validateForm();
                 }
            }
        });

        // Remove Button Logic
        removeBtn.addEventListener('click', function() {
            if (actionHidden.value === 'add') {
                 // Item baru -> Hapus dari DOM
                 if(!productSelect.disabled) $(productSelect).select2('destroy');
                 newRow.remove();
            } else {
                 // Item lama -> Set Qty 0 (Soft Delete request)
                 quantityInput.value = 0;
                 quantityInput.dispatchEvent(new Event('input')); // Trigger logic di atas
            }
            validateForm();
        });

        itemIndex++;
        validateForm();
    }

     function loadOriginalItems() {
        itemsContainer.innerHTML = '';
        itemIndex = 0;
        Object.values(originalOrderItems).forEach(item => {
            addRequestItemRow(item);
        });
        validateForm();
    }

    // Listeners
    requestTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleModifySection);
    });
    
    addItemBtn.addEventListener('click', () => addRequestItemRow());

    // Init state
    toggleModifySection();
});
</script>
@endpush
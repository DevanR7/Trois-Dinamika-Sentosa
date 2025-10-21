@extends('layouts.client')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        {{-- Tombol Kembali --}}
        <a href="{{ route('client.sales-orders.show', $order->order_id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Detail Pesanan
        </a>
        <h2 class="fw-bold mb-0">Ajukan Perubahan Pesanan: {{ $order->order_number }}</h2>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('client.sales-orders.requestChange.store', $order->order_id) }}" method="POST" id="change-request-form">
                @csrf

                {{-- PILIHAN JENIS PERMINTAAN --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">Jenis Permintaan <span class="text-danger">*</span></label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="request_type" id="type_cancel" value="cancel" {{ old('request_type') == 'cancel' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="type_cancel">
                            Batalkan Seluruh Pesanan
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="request_type" id="type_modify" value="modify" {{ old('request_type', 'modify') == 'modify' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="type_modify">
                            Ubah Item/Jumlah Pesanan
                        </label>
                    </div>
                </div>

                {{-- CATATAN KLIEN --}}
                <div class="mb-4">
                    <label for="client_notes" class="form-label fw-semibold">Alasan / Catatan Permintaan</label>
                    <textarea class="form-control" name="client_notes" id="client_notes" rows="3" placeholder="Jelaskan alasan pembatalan atau detail perubahan item...">{{ old('client_notes') }}</textarea>
                </div>

                {{-- BAGIAN UBAH ITEM (MUNCUL JIKA 'modify' DIPILIH) --}}
                <div id="modify-items-section" class="mb-4 {{ old('request_type', 'modify') == 'modify' ? '' : 'd-none' }}">
                    <h5 class="fw-semibold mb-3">Ajukan Perubahan Item</h5>
                    <p class="text-muted small">Ubah kuantitas item yang ada (isi 0 untuk menghapus), atau tambahkan item baru. Harga satuan akan menggunakan harga terbaru saat permintaan diproses.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center" style="width: 15%;">Kuantitas Asli</th>
                                    <th class="text-center" style="width: 20%;">Kuantitas Diminta</th>
                                    <th class="text-center" style="width: 10%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="request-items-container">
                                {{-- Item asli akan dimuat di sini oleh JavaScript --}}
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-item-btn" class="btn btn-secondary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Item Baru
                    </button>
                    {{-- Pesan error validasi JS --}}
                    <div id="items-validation-error" class="text-danger small mt-2 d-none">Pastikan semua item baru sudah dipilih produknya.</div>
                </div>

                {{-- TOMBOL SUBMIT --}}
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('client.sales-orders.show', $order->order_id) }}" class="btn btn-light me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- TEMPLATE UNTUK BARIS ITEM (digunakan JavaScript) --}}
<template id="item-row-template">
    <tr>
        <td>
            <select class="form-select form-select-sm product-select" disabled> {{-- Select dinamis --}}
                <option value="">-- Pilih Produk --</option>
                 @foreach ($products as $product)
                    <option value="{{ $product->product_id }}" data-price="{{ $product->selling_price ?? 0 }}">{{ $product->product_name }}</option>
                @endforeach
            </select>
            <input type="hidden" class="product-id-hidden">
             <input type="hidden" class="original-quantity-hidden">
             <input type="hidden" class="action-hidden">
        </td>
        <td class="text-center original-quantity-display">-</td>
        <td><input type="number" class="form-control form-control-sm requested-quantity" value="1" min="0"></td> {{-- min="0" agar bisa dihapus --}}
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
{{-- Select2 CSS & JS jika belum ada di layout utama --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> {{-- Select2 butuh jQuery --}}
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
    const itemsValidationError = document.getElementById('items-validation-error'); // Elemen pesan error

    // Data item order asli dari PHP
    const originalOrderItems = @json($order->items->keyBy('product_id'));
    // const allProducts = @json($products->keyBy('product_id')); // Tidak perlu lagi jika select sudah di template
    let itemIndex = 0; // Untuk penamaan input array

    // --- FUNGSI VALIDASI ---
    function validateForm() {
        let isValid = true;
        itemsValidationError.classList.add('d-none'); // Sembunyikan error dulu

        // Hanya validasi jika tipe 'modify' dipilih
        if (form.querySelector('input[name="request_type"]:checked').value === 'modify') {
            const itemRows = itemsContainer.querySelectorAll('tr');
            if (itemRows.length === 0) {
                 // Jika tidak ada item sama sekali saat modify, anggap tidak valid
                 isValid = false;
                 itemsValidationError.textContent = 'Harap tambahkan setidaknya satu item.';
                 itemsValidationError.classList.remove('d-none');
            } else {
                 itemRows.forEach(row => {
                     const action = row.querySelector('.action-hidden').value;
                     const productId = row.querySelector('.product-id-hidden').value;
                     const selectEl = $(row.querySelector('.product-select'));

                     // Item baru (action='add') wajib punya product_id
                     if (action === 'add' && !productId) {
                         isValid = false;
                         selectEl.addClass('is-invalid'); // Beri border merah
                         itemsValidationError.textContent = 'Pastikan semua item baru sudah dipilih produknya.';
                         itemsValidationError.classList.remove('d-none');
                     } else {
                          selectEl.removeClass('is-invalid'); // Hapus border merah jika valid
                     }
                 });
            }
        }
        // Aktifkan/nonaktifkan tombol submit berdasarkan validasi
        submitBtn.disabled = !isValid;
    }

    // --- FUNGSI UTAMA ---

    function toggleModifySection() {
        if (form.querySelector('input[name="request_type"]:checked').value === 'modify') {
            modifySection.classList.remove('d-none');
            if (itemsContainer.children.length === 0) {
                loadOriginalItems();
            }
        } else {
            modifySection.classList.add('d-none');
        }
        validateForm(); // Validasi ulang saat section berubah
    }

    function addRequestItemRow(itemData = null, isNew = false) {
        const templateClone = itemRowTemplate.content.cloneNode(true);
        const newRow = templateClone.querySelector('tr');
        const productSelect = newRow.querySelector('.product-select');
        const quantityInput = newRow.querySelector('.requested-quantity');
        const originalQtyDisplay = newRow.querySelector('.original-quantity-display');
        const removeBtn = newRow.querySelector('.remove-item-btn');
        const productIdHidden = newRow.querySelector('.product-id-hidden');
        const originalQtyHidden = newRow.querySelector('.original-quantity-hidden');
        const actionHidden = newRow.querySelector('.action-hidden');

        productIdHidden.name = `items[${itemIndex}][product_id]`;
        quantityInput.name = `items[${itemIndex}][quantity]`;
        originalQtyHidden.name = `items[${itemIndex}][original_quantity]`;
        actionHidden.name = `items[${itemIndex}][action]`;

        if (itemData) {
            productSelect.value = itemData.product_id;
            productSelect.disabled = true;
            originalQtyDisplay.textContent = itemData.quantity;
            quantityInput.value = itemData.quantity;
            productIdHidden.value = itemData.product_id;
            originalQtyHidden.value = itemData.quantity;
            actionHidden.value = 'update_qty';
        } else {
             productSelect.disabled = false;
             originalQtyDisplay.textContent = 'Baru';
             quantityInput.value = 1;
             productIdHidden.value = '';
             originalQtyHidden.value = '';
             actionHidden.value = 'add';
        }

        itemsContainer.appendChild(newRow);

        // Inisialisasi Select2 hanya untuk baris baru atau jika tidak disabled
        if (!productSelect.disabled) {
           const select2Instance = $(productSelect).select2({
                 placeholder: '-- Pilih Produk --',
                 theme: 'bootstrap-5',
                 dropdownParent: $(productSelect).parent()
            }).on('change', function() {
                 productIdHidden.value = $(this).val();
                 validateForm(); // Validasi saat produk dipilih
            });
        }

        quantityInput.addEventListener('input', function() {
             const requestedQty = parseInt(this.value);
             const originalQty = parseInt(originalQtyHidden.value);

            if (actionHidden.value !== 'add') {
                 if (requestedQty === 0) {
                     actionHidden.value = 'remove';
                     newRow.classList.add('table-danger');
                 } else { // Jika > 0, selalu 'update_qty' meskipun sama dengan asli
                     actionHidden.value = 'update_qty';
                     newRow.classList.remove('table-danger');
                 }
            } else {
                 if (requestedQty === 0) {
                     $(productSelect).select2('destroy');
                     newRow.remove();
                     validateForm(); // Validasi ulang setelah baris dihapus
                 }
            }
        });

        removeBtn.addEventListener('click', function() {
            if (actionHidden.value === 'add') {
                 $(productSelect).select2('destroy');
                 newRow.remove();
            } else {
                 quantityInput.value = 0;
                 quantityInput.dispatchEvent(new Event('input'));
            }
            validateForm(); // Validasi ulang setelah item dihapus/ditandai hapus
        });

        itemIndex++;
        validateForm(); // Validasi saat baris ditambahkan
    }

     function loadOriginalItems() {
        itemsContainer.innerHTML = '';
        itemIndex = 0;
        Object.values(originalOrderItems).forEach(item => {
            addRequestItemRow(item, false);
        });
        validateForm(); // Validasi setelah item asli dimuat
    }

    // --- EVENT LISTENERS ---
    requestTypeRadios.forEach(radio => {
        radio.addEventListener('change', toggleModifySection);
    });
    addItemBtn.addEventListener('click', () => addRequestItemRow(null, true));

    // --- INISIALISASI ---
    toggleModifySection(); // Panggil ini dulu untuk setup awal

});
</script>
@endpush
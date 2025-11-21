@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Buat Retur Pembelian</h3>
            <p class="text-muted mb-0 small">Kembalikan barang ke supplier dan sesuaikan tagihan/deposit.</p>
        </div>
        <div>
            <a href="{{ route('purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            {{-- ERROR ALERT --}}
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('purchase-returns.store') }}" method="POST">
                @csrf
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-arrow-return-left"></i> Form Retur Barang</div>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        {{-- 1. INFORMASI DASAR --}}
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label for="purchase_order_id" class="form-label fw-bold small text-muted">PILIH PO ASLI</label>
                                <select name="purchase_order_id" id="purchase_order_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Nomor PO --</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->po_id }}">{{ $po->po_number }} - {{ $po->supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Hanya PO dengan status 'received' atau 'completed' yang muncul.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="return_date" class="form-label fw-bold small text-muted">TANGGAL RETUR</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <hr class="border-dashed">

                        {{-- 2. TABEL ITEM --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-3">Item yang Diretur</h6>
                            
                            <div id="instruction-text" class="text-center py-5 bg-light rounded border border-dashed text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                Silakan pilih Nomor PO terlebih dahulu untuk memuat item.
                            </div>

                            <div class="table-responsive" id="items-table-container" style="display: none;">
                                <table class="table table-hover table-transaction align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%;">Produk</th>
                                            <th class="text-center" style="width: 15%;">Qty Beli</th>
                                            <th class="text-end" style="width: 20%;">Harga Satuan</th>
                                            <th style="width: 25%;">Qty Retur</th>
                                        </tr>
                                    </thead>
                                    <tbody id="return-items-body"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 3. CATATAN --}}
                        <div class="mb-4">
                            <label for="notes" class="form-label fw-bold small text-muted">ALASAN RETUR (CATATAN)</label>
                            <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="Contoh: Barang rusak saat pengiriman, kemasan cacat, dll..."></textarea>
                        </div>

                        <hr class="border-dashed">

                        {{-- 4. AKSI PENANGANAN (DESIGN CARD) --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted mb-3">METODE PENGEMBALIAN DANA</label>
                            
                            <div class="d-flex flex-column gap-2">
                                {{-- Opsi 1: Potong Tagihan --}}
                                <label class="card p-3 border border-primary border-opacity-25 shadow-sm cursor-pointer position-relative bg-white" for="deduct_invoice">
                                    <div class="d-flex align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="return_handling_type" id="deduct_invoice" value="deduct_invoice" checked style="transform: scale(1.2);">
                                        </div>
                                        <div class="ms-3">
                                            <span class="d-block fw-bold text-dark">Potong Tagihan PO (Cut Invoice)</span>
                                            <small class="text-muted">Nilai retur akan mengurangi sisa hutang pada PO ini secara langsung. (Pilih ini jika PO belum lunas)</small>
                                        </div>
                                    </div>
                                </label>

                                {{-- Opsi 2: Simpan Deposit --}}
                                <label class="card p-3 border border-secondary border-opacity-25 shadow-sm cursor-pointer position-relative bg-white" for="store_as_deposit">
                                    <div class="d-flex align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="return_handling_type" id="store_as_deposit" value="store_as_deposit" style="transform: scale(1.2);">
                                        </div>
                                        <div class="ms-3">
                                            <span class="d-block fw-bold text-dark">Simpan sebagai Saldo Deposit</span>
                                            <small class="text-muted">Nilai retur akan menjadi Deposit Supplier yang bisa dipakai untuk memotong PO lain nanti. (Pilih ini jika PO sudah lunas)</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ route('purchase-returns.index') }}" class="btn btn-light border">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Simpan Retur
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // (LOGIKA JS ANDA SAMA PERSIS, HANYA FORMATTING CURRENCY SEDIKIT SAYA RAPIKAN)
    
    const poSelect = $('#purchase_order_id');
    const itemsContainer = $('#return-items-body');
    const tableContainer = $('#items-table-container');
    const instructionText = $('#instruction-text');

    poSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor PO --',
        width: '100%'
    });

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    poSelect.on('change', function() {
        const poId = $(this).val();
        if (!poId) {
            tableContainer.hide();
            instructionText.show();
            itemsContainer.empty();
            return;
        }
        
        instructionText.html('<div class="spinner-border text-primary" role="status"></div><div class="mt-2">Memuat data item...</div>').show();
        tableContainer.hide();

        fetch(`/api/purchase-orders/${poId}/items`)
            .then(response => response.json())
            .then(data => {
                itemsContainer.empty();
                if (data.items && data.items.length > 0) {
                    let hasReturnableItems = false;

                    data.items.forEach((item, index) => {
                        const maxQty = item.quantity - (item.quantity_returned || 0);
                        
                        if (maxQty > 0) {
                            hasReturnableItems = true;
                            const rowHTML = `
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">${item.product.product_name}</div>
                                        <small class="text-muted">Sudah diretur: ${item.quantity_returned || 0} / ${item.quantity}</small>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark border">${item.quantity}</span>
                                    </td>
                                    <td class="text-end align-middle text-muted">${formatRupiah(item.price_per_unit)}</td>
                                    <td>
                                        <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                                        <div class="input-group input-group-sm">
                                            <input 
                                                type="number" 
                                                name="items[${index}][quantity]" 
                                                class="form-control return-qty-input fw-bold text-center" 
                                                min="0" 
                                                max="${maxQty}" 
                                                placeholder="0"
                                            >
                                            <span class="input-group-text text-muted small">/${maxQty}</span>
                                        </div>
                                        <div class="text-danger small mt-1 d-none qty-error-message" style="font-size: 0.75rem;">Maks: ${maxQty}</div>
                                    </td>
                                </tr>
                            `;
                            const newRow = $(rowHTML);

                            newRow.find('.return-qty-input').on('input', function() {
                                const input = $(this);
                                const errorMessage = newRow.find('.qty-error-message');
                                const currentValue = parseInt(input.val(), 10);
                                const maxValue = parseInt(input.attr('max'), 10);

                                if (currentValue > maxValue) {
                                    errorMessage.removeClass('d-none');
                                    input.addClass('is-invalid');
                                } else {
                                    errorMessage.addClass('d-none');
                                    input.removeClass('is-invalid');
                                }
                            });
                            
                            itemsContainer.append(newRow);
                        }
                    });

                    if(hasReturnableItems){
                        instructionText.hide();
                        tableContainer.fadeIn();
                    } else {
                        instructionText.html('<span class="text-danger"><i class="bi bi-x-circle"></i> Semua item pada PO ini sudah diretur sepenuhnya.</span>').show();
                    }
                } else {
                    instructionText.text('Tidak ada item yang ditemukan pada PO ini.').show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                instructionText.text('Gagal memuat data. Silakan coba lagi.').show();
            });
    });
});
</script>
@endpush
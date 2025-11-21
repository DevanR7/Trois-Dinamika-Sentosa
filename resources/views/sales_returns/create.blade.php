@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Buat Retur Penjualan</h3>
            <p class="text-muted mb-0 small">Catat pengembalian barang dari pelanggan (Klien)</p>
        </div>
        <div>
            <a href="{{ route('sales-returns.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('sales-returns.store') }}" method="POST">
                @csrf
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-reply-all"></i> Form Data Retur</div>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        {{-- 1. INFO DASAR --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="sales_invoice_id" class="form-label fw-bold small text-muted">PILIH INVOICE ASAL</label>
                                <select name="sales_invoice_id" id="sales_invoice_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Nomor Invoice --</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->invoice_id }}">{{ $invoice->invoice_number }} - {{ $invoice->client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="return_date" class="form-label fw-bold small text-muted">TANGGAL RETUR</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <hr class="border-dashed">

                        {{-- 2. ITEM RETUR --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-3">Pilih Item yang Dikembalikan</h6>
                            <p class="text-muted small fst-italic mb-3" id="instruction-text">
                                <i class="bi bi-info-circle me-1"></i> Silakan pilih Nomor Invoice terlebih dahulu.
                            </p>
                            
                            <div class="table-responsive border rounded" id="items-table-container" style="display: none;">
                                <table class="table table-hover table-transaction align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 40%;">Produk</th>
                                            <th class="text-center" style="width: 15%;">Qty Beli</th>
                                            <th class="text-end" style="width: 20%;">Harga Jual (@)</th>
                                            <th style="width: 25%;">Qty Retur</th>
                                        </tr>
                                    </thead>
                                    <tbody id="return-items-body"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 3. CATATAN & AKSI --}}
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="notes" class="form-label fw-bold small text-muted">ALASAN RETUR (CATATAN)</label>
                                <textarea class="form-control bg-light" name="notes" id="notes" rows="4" placeholder="Contoh: Barang cacat, salah kirim, dll..."></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted mb-2">METODE PENGEMBALIAN DANA</label>
                                <div class="card border bg-light">
                                    <div class="card-body p-3">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="return_handling_type" id="deduct_invoice" value="deduct_invoice" checked>
                                            <label class="form-check-label fw-bold text-dark" for="deduct_invoice">
                                                Potong Tagihan Invoice
                                            </label>
                                            <small class="d-block text-muted">Mengurangi sisa tagihan pada invoice asli (Jika belum lunas).</small>
                                        </div>
                                        <hr class="my-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="return_handling_type" id="store_as_credit" value="store_as_credit">
                                            <label class="form-check-label fw-bold text-dark" for="store_as_credit">
                                                Simpan sebagai Saldo Kredit
                                            </label>
                                            <small class="d-block text-muted">Menambah saldo deposit klien untuk pesanan berikutnya (Jika invoice lunas).</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('sales-returns.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Retur</button>
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
    const invoiceSelect = $('#sales_invoice_id');
    const itemsContainer = $('#return-items-body');
    const tableContainer = $('#items-table-container');
    const instructionText = $('#instruction-text');

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    invoiceSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor Invoice --',
        width: '100%'
    });

    invoiceSelect.on('change', function() {
        const invoiceId = $(this).val();
        if (!invoiceId) {
            tableContainer.hide();
            instructionText.show();
            itemsContainer.empty();
            return;
        }

        instructionText.html('<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat item...').show();
        tableContainer.hide();

        fetch(`/api/invoices/${invoiceId}/items`)
            .then(response => response.json())
            .then(data => {
                itemsContainer.empty();
                if (data.items && data.items.length > 0) {
                    const discountRate = (data.invoice.discount_percentage || 0) / 100;

                    data.items.forEach((item, index) => {
                        const maxQty = item.quantity - item.quantity_returned;
                        
                        if (maxQty > 0) {
                            const priceAfterDiscount = item.price_per_unit * (1 - discountRate);

                            const rowHTML = `
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">${item.product.product_name}</div>
                                        <small class="text-muted">Sudah diretur: ${item.quantity_returned} / ${item.quantity}</small>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark border">${item.quantity}</span>
                                    </td>
                                    <td class="text-end align-middle text-muted">${formatRupiah(priceAfterDiscount)}</td>
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
                                        <div class="text-danger small mt-1 d-none qty-error-message" style="font-size: 0.7rem;">Maks: ${maxQty}</div>
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
                    instructionText.hide();
                    tableContainer.fadeIn();
                } else {
                    instructionText.html('<span class="text-danger"><i class="bi bi-x-circle"></i> Tidak ada item yang bisa diretur.</span>').show();
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
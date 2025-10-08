@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Buat Retur Penjualan Baru</h4>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <form action="{{ route('sales-returns.store') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="sales_invoice_id" class="form-label fw-semibold">Pilih Invoice Asli</label>
                                <select name="sales_invoice_id" id="sales_invoice_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari dan Pilih Nomor Invoice --</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->invoice_id }}">{{ $invoice->invoice_number }} - {{ $invoice->client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="return_date" class="form-label fw-semibold">Tanggal Retur</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>

                        <h5 class="fw-semibold mb-3">Item yang Diretur</h5>
                        <p class="text-muted" id="instruction-text">Pilih invoice terlebih dahulu untuk menampilkan item.</p>
                        
                        <div class="table-responsive" id="items-table-container" style="display: none;">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-center">Qty Dibeli</th>
                                        <th class="text-end">Harga Satuan</th>
                                        <th style="width: 20%;">Qty Diretur</th>
                                    </tr>
                                </thead>
                                <tbody id="return-items-body">
                                    {{-- Item dari invoice akan dimuat di sini oleh JavaScript --}}
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <label for="notes" class="form-label fw-semibold">Catatan (Alasan Retur)</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('sales-returns.index') }}" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Retur</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Script untuk memuat item invoice secara dinamis --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const invoiceSelect = $('#sales_invoice_id');
    const itemsContainer = $('#return-items-body');
    const tableContainer = $('#items-table-container');
    const instructionText = $('#instruction-text');

    // Inisialisasi Select2
    invoiceSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor Invoice --'
    });

    invoiceSelect.on('change', function() {
        const invoiceId = $(this).val();
        if (!invoiceId) {
            tableContainer.hide();
            instructionText.show();
            itemsContainer.empty();
            return;
        }

        instructionText.text('Memuat item...').show();
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
                                    <td>${item.product.product_name}<br><small class="text-muted">Sudah diretur: ${item.quantity_returned}</small></td>
                                    <td class="text-center align-middle">${item.quantity}</td>
                                    <td class="text-end align-middle">${formatRupiah(priceAfterDiscount)}</td>
                                    <td>
                                        <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                                        <input 
                                            type="number" 
                                            name="items[${index}][quantity]" 
                                            class="form-control form-control-sm return-qty-input" 
                                            min="0" 
                                            max="${maxQty}" 
                                            placeholder="Maks: ${maxQty}"
                                        >
                                        
                                        <div class="text-danger small mt-1 d-none qty-error-message">Jumlah melebihi batas retur!</div>
                                    </td>
                                </tr>
                            `;
                            const newRow = $(rowHTML);

                            // [BARU] Tambahkan event listener untuk validasi real-time
                            newRow.find('.return-qty-input').on('input', function() {
                                const input = $(this);
                                const errorMessage = newRow.find('.qty-error-message');
                                const currentValue = parseInt(input.val(), 10);
                                const maxValue = parseInt(input.attr('max'), 10);

                                if (currentValue > maxValue) {
                                    errorMessage.removeClass('d-none'); // Tampilkan error
                                    input.addClass('is-invalid'); // Tambah border merah
                                } else {
                                    errorMessage.addClass('d-none'); // Sembunyikan error
                                    input.removeClass('is-invalid'); // Hapus border merah
                                }
                            });
                            
                            itemsContainer.append(newRow);
                        }
                    });
                    instructionText.hide();
                    tableContainer.show();
                } else {
                    instructionText.text('Tidak ada item yang bisa diretur dari invoice ini.').show();
                }
            })
            .catch(error => console.error('Error:', error));
    });

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }
});
</script>
@endpush
@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white"><h4 class="mb-0">Buat Retur Pembelian Baru</h4></div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <form action="{{ route('purchase-returns.store') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="purchase_order_id" class="form-label fw-semibold">Pilih Pesanan Pembelian (PO) Asli</label>
                                <select name="purchase_order_id" id="purchase_order_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari dan Pilih Nomor PO --</option>
                                    @foreach($purchaseOrders as $po)
                                        <option value="{{ $po->po_id }}">{{ $po->po_number }} - {{ $po->supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="return_date" class="form-label fw-semibold">Tanggal Retur</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <h5 class="fw-semibold mb-3">Item yang Diretur ke Supplier</h5>
                        <p class="text-muted" id="instruction-text">Pilih PO terlebih dahulu untuk menampilkan item.</p>
                        <div class="table-responsive" id="items-table-container" style="display: none;">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-center">Qty Dibeli</th>
                                        <th class="text-end">Harga Beli Satuan</th>
                                        <th style="width: 20%;">Qty Diretur</th>
                                    </tr>
                                </thead>
                                <tbody id="return-items-body"></tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <label for="notes" class="form-label fw-semibold">Catatan (Alasan Retur)</label>
                            <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('purchase-returns.index') }}" class="btn btn-secondary me-2">Batal</a>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const poSelect = $('#purchase_order_id');
    const itemsContainer = $('#return-items-body');
    const tableContainer = $('#items-table-container');
    const instructionText = $('#instruction-text');

    poSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari dan Pilih Nomor PO --'
    });

    poSelect.on('change', function() {
        const poId = $(this).val();
        if (!poId) {
            tableContainer.hide();
            instructionText.show();
            itemsContainer.empty();
            return;
        }
        instructionText.text('Memuat item...').show();
        tableContainer.hide();

        fetch(`/api/purchase-orders/${poId}/items`)
            .then(response => response.json())
            .then(data => {
                itemsContainer.empty();
                if (data.items && data.items.length > 0) {
                    data.items.forEach((item, index) => {
                        const row = `
                            <tr>
                                <td>${item.product.product_name}</td>
                                <td class="text-center">${item.quantity}</td>
                                <td class="text-end">${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(item.price_per_unit)}</td>
                                <td>
                                    <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                                    <input type="hidden" name="items[${index}][price_per_unit]" value="${item.price_per_unit}">
                                    <input type="number" name="items[${index}][quantity]" class="form-control form-control-sm" min="0" max="${item.quantity}" placeholder="0">
                                </td>
                            </tr>
                        `;
                        itemsContainer.append(row);
                    });
                    instructionText.hide();
                    tableContainer.show();
                } else {
                    instructionText.text('Tidak ada item ditemukan di PO ini.').show();
                }
            })
            .catch(error => console.error('Error:', error));
    });
});
</script>
@endpush
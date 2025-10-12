@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN DENGAN TOMBOL AKSI --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Detail Pesanan: {{ $purchaseOrder->po_number }}</h2>
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
            @can('receive', $purchaseOrder)
                @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                    <form id="receive-goods-form" action="{{ route('purchase-orders.receive', $purchaseOrder->po_id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success"><i class="bi bi-box-seam me-1"></i> Tandai Barang Diterima</button>
                    </form>
                @endif
            @endcan
            @can('pay', $purchaseOrder)
                @if($purchaseOrder->payment_status != 'paid')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal" id="add-payment-btn">
                        <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran
                    </button>
                @endif
            @endcan
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-gear"></i> Opsi</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if (in_array($purchaseOrder->status, ['draft', 'ordered']))
        <li>
            <a class="dropdown-item" href="{{ route('purchase-orders.edit', $purchaseOrder->po_id) }}">
                <i class="bi bi-pencil-square me-2"></i> Edit Pesanan
            </a>
        </li>
        @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('purchase-orders.pdf', $purchaseOrder->po_id) }}"><i class="bi bi-file-earmark-pdf me-2"></i> Download PDF</a></li> 
                    @can('cancel', $purchaseOrder)
                        @if(in_array($purchaseOrder->status, ['draft', 'ordered']))
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('purchase-orders.cancel', $purchaseOrder->po_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin MEMBATALKAN pesanan ini?');">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i> Batalkan Pesanan</button>
                                </form>
                            </li>
                        @endif
                    @endcan
                </ul>
            </div>
        </div>
    </div>

    {{-- KARTU DETAIL UTAMA --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            {{-- Info Supplier & Status --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">Supplier</h5>
                    <p class="mb-0">{{ $purchaseOrder->supplier->supplier_name }}</p>
                    <p class="text-muted">{{ $purchaseOrder->supplier->address ?? 'Alamat tidak tersedia' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>No. Faktur Supplier:</strong> 
                        <a href="#" data-bs-toggle="modal" data-bs-target="#supplierInvoiceModal" class="text-decoration-none" title="Klik untuk input/edit">
                            @if($purchaseOrder->supplier_invoice_number)
                                <span class="fw-bold text-primary">{{ $purchaseOrder->supplier_invoice_number }}</span>
                            @else
                                <span class="text-muted fst-italic">(Belum Diinput)</span>
                            @endif
                        </a>
                    </p>
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($purchaseOrder->order_date)->format('d F Y') }}</p>
                    @if($purchaseOrder->due_date)<p class="mb-1"><strong>Jatuh Tempo:</strong> {{ optional($purchaseOrder->due_date)->format('d F Y') }}</p>@endif
                    <p class="mb-1"><strong>Status Barang:</strong>
                        @if($purchaseOrder->status == 'completed') <span class="badge bg-success">Diterima</span>
                        @elseif($purchaseOrder->status == 'cancelled') <span class="badge bg-danger">Dibatalkan</span>
                        @else <span class="badge bg-warning text-dark">{{ Str::title($purchaseOrder->status) }}</span>
                        @endif
                    </p>
                    <p class="mb-1"><strong>Status Pembayaran:</strong>
                        @if($purchaseOrder->payment_status == 'paid') <span class="badge bg-primary">Lunas</span>
                        @elseif($purchaseOrder->payment_status == 'partially_paid') <span class="badge bg-info text-dark">Cicil</span>
                        @else <span class="badge bg-danger">Belum Lunas</span>
                        @endif
                    </p>
                </div>
            </div>
            <hr>
            
            {{-- Tabel Rincian Item --}}
            <h5 class="fw-semibold mt-4">Rincian Item Dipesan</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 5%;">No</th>
                            <th>Produk</th>
                            <th class="text-center" style="width: 15%;">Kuantitas</th>
                            <th class="text-end" style="width: 20%;">Harga Final / Unit</th>
                            <th class="text-end" style="width: 20%;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                {{ $item->product->product_name ?? 'Produk Dihapus' }}
                                @if(isset($item->discounts) && $item->discounts->isNotEmpty())
                                <small class="d-block text-muted">
                                    Harga Awal: Rp {{ number_format($item->price_per_unit, 0, ',', '.') }} |
                                    Diskon: {{ $item->discounts->pluck('percentage')->join('%, ') }}%
                                </small>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->quantity }} {{ $item->product->unit->name ?? '' }}</td>
                            <td class="text-end">
                                @if($item->quantity > 0)
                                    Rp {{ number_format($item->subtotal / $item->quantity, 0, ',', '.') }}
                                @else
                                    Rp 0
                                @endif
                            </td>
                            <td class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- BAGIAN YANG DIUBAH: Ringkasan Keuangan dengan Retur --}}
            <div class="row mt-4">
                {{-- KOLOM KIRI: RINGKASAN PEMBELIAN --}}
                <div class="col-md-6">
                    <h5 class="fw-semibold mb-3">Rincian Perhitungan</h5>
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal Barang</span><span class="fw-medium">Rp {{ number_format($purchaseOrder->subtotal ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Diskon / Fee @if($purchaseOrder->disc_fee_percent > 0) <small>({{ $purchaseOrder->disc_fee_percent }}%)</small> @endif</span><span class="text-danger">(-) Rp {{ number_format($purchaseOrder->disc_fee_amount ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Diskon Pembulatan</span><span class="text-danger">(-) Rp {{ number_format($purchaseOrder->rounding_discount_amount ?? 0, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between mb-2"><span>DPP <small class="text-muted">@if($purchaseOrder->custom_dpp_factor) (Faktor: {{ $purchaseOrder->custom_dpp_factor }}) @endif</small></span><span class="fw-medium">Rp {{ number_format($purchaseOrder->dpp ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>PPN ({{ $purchaseOrder->tax->rate ?? 0 }}%)</span><span>(+) Rp {{ number_format($purchaseOrder->ppn ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Ongkos Kirim</span><span>(+) Rp {{ number_format($purchaseOrder->shipping_amount ?? 0, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold"><span>Grand Total Pembelian</span><span>Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}</span></div>
                    </div>
                </div>

                {{-- KOLOM KANAN: RINGKASAN KEUANGAN --}}
                <div class="col-md-6">
                    <h5 class="fw-semibold mb-3">Ringkasan Keuangan</h5>
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between mb-2"><span>Total Tagihan Awal</span><span>Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between text-warning mb-2"><span>Total Retur</span><span>(-) Rp {{ number_format($purchaseOrder->total_returned, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-semibold mb-2"><span>Tagihan Bersih</span><span>Rp {{ number_format($purchaseOrder->total_amount - $purchaseOrder->total_returned, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between text-success mb-2"><span>Sudah Dibayar</span><span>(+) Rp {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        @php
                            $sisaUtang = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                        @endphp
                        <div class="d-flex justify-content-between fw-bold fs-5 {{ $sisaUtang > 0 ? 'text-danger' : 'text-success' }}">
                            <span>Sisa Utang</span>
                            <span>Rp {{ number_format($sisaUtang, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @if($purchaseOrder->notes)<div class="mt-4"><h6 class="fw-semibold">Catatan:</h6><p class="text-muted fst-italic bg-light p-3 rounded">{{ $purchaseOrder->notes }}</p></div>@endif
        </div>
    </div>

    
    @if($purchaseOrder->payments->isNotEmpty())
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header">
            <h5 class="mb-0">Riwayat Pembayaran</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th>Metode</th>
                            <th class="text-end">Jumlah</th>
                            <th>Catatan</th>
                            <th>Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td>{{ Str::title(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>{{ $payment->notes }}</td>
                            <td>{{ $payment->receivedBy->full_name ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- MODAL UNTUK TAMBAH PEMBAYARAN --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pembayaran Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchase-orders.payments.store', $purchaseOrder->po_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @php
                        $sisaTagihan = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                    @endphp
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between">
                            <span class="fw-medium">Total Tagihan:</span>
                            <span>Rp {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-medium">Total Retur:</span>
                            <span>(-) Rp {{ number_format($purchaseOrder->total_returned, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-medium">Sudah Dibayar:</span>
                            <span>(+) Rp {{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Sisa Tagihan:</span>
                            <span>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Tanggal Pembayaran</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount-formatted" class="form-label">Jumlah Pembayaran (Rp)</label>
                        <input type="text" class="form-control" id="amount-formatted" required>
                        <input type="hidden" name="amount" id="amount">
                        <div id="amount-error" class="text-danger small mt-1"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Metode Pembayaran</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="manual_transfer">Transfer Manual</option>
                            <option value="cash">Tunai (Cash)</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (Opsional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal untuk Input Nomor Faktur Supplier --}}
<div class="modal fade" id="supplierInvoiceModal" tabindex="-1" aria-labelledby="supplierInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierInvoiceModalLabel">Input Nomor Faktur Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('purchase-orders.addSupplierInvoice', $purchaseOrder->po_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="supplier_invoice_number" class="form-label">No. Faktur dari Supplier</label>
                        <input type="text" class="form-control" id="supplier_invoice_number" name="supplier_invoice_number" value="{{ $purchaseOrder->supplier_invoice_number }}" required>
                        <div class="form-text">Masukkan nomor yang tertera di surat jalan atau faktur dari supplier.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // =======================================================
        // BAGIAN 1: LOGIKA SWEETALERT UNTUK TERIMA BARANG
        // =======================================================
        const receiveGoodsForm = document.getElementById('receive-goods-form');
        if (receiveGoodsForm) {
            receiveGoodsForm.addEventListener('submit', function(event) {
                event.preventDefault(); 
                Swal.fire({
                    title: 'Konfirmasi Penerimaan',
                    text: "Apakah Anda yakin semua barang untuk pesanan ini telah diterima? Stok akan diperbarui.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Sudah Diterima!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        event.target.submit();
                    }
                });
            });
        }

        // =======================================================
        // BAGIAN 2: LOGIKA AUTONUMERIC UNTUK MODAL PEMBAYARAN
        // =======================================================
        const addPaymentBtn = document.getElementById('add-payment-btn');
        const amountFormattedInput = document.getElementById('amount-formatted');
        const amountHiddenInput = document.getElementById('amount');
        const amountError = document.getElementById('amount-error');

        if (amountFormattedInput) {
            const autoNumericInstance = new AutoNumeric(amountFormattedInput, {
                decimalCharacter: ',',
                digitGroupSeparator: '.',
                currencySymbol: '',
                decimalPlaces: 0,
                minimumValue: '0'
            });

            if (addPaymentBtn) {
                addPaymentBtn.addEventListener('click', function() {
                    @php
                        $sisaTagihan = $purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid;
                    @endphp
                     const remainingBalance = {{ ($purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid) ?? 0 }};

                    autoNumericInstance.set(remainingBalance);
                    amountHiddenInput.value = remainingBalance;
                    autoNumericInstance.update({ maximumValue: remainingBalance });
                    amountError.textContent = '';
                });

                amountFormattedInput.addEventListener('autoNumeric:rawValueModified', function(event) {
                    const rawValue = event.detail.newRawValue;
                    amountHiddenInput.value = rawValue;

                      const remainingBalance = {{ ($purchaseOrder->total_amount - $purchaseOrder->total_returned - $purchaseOrder->amount_paid) ?? 0 }};
                    if (parseFloat(rawValue) > remainingBalance) {
                        amountError.textContent = 'Jumlah pembayaran tidak boleh melebihi sisa tagihan!';
                    } else {
                        amountError.textContent = '';
                    }
                });
            }
        }
    });
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- HEADER HALAMAN DENGAN TOMBOL AKSI --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold mb-0">Detail Invoice: {{ $invoice->invoice_number }}</h2>
        
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
            
            @if(!in_array($invoice->status, ['paid', 'cancelled']))
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" id="add-payment-btn">
                    <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran
                </button>
            @endif
            
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear"></i> Opsi
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                     <li><a class="dropdown-item" href="{{ route('invoices.edit', $invoice->invoice_id) }}"><i class="bi bi-pencil-square me-2"></i> Edit Invoice</a></li>
                     <li><hr class="dropdown-divider"></li>
                     <li><a class="dropdown-item" href="{{ route('invoices.pdf', $invoice->invoice_id) }}"><i class="bi bi-file-earmark-pdf me-2"></i> Download PDF</a></li>
                     @if(!in_array($invoice->status, ['paid', 'cancelled']))
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin membatalkan invoice ini?');">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i> Batalkan Invoice</button>
                            </form>
                        </li>
                     @endif
                </ul>
            </div>
        </div>
    </div>

    {{-- KARTU DETAIL UTAMA --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">Klien</h5>
                    <p class="mb-0">{{ $invoice->client->client_name }}</p>
                    <p class="text-muted">{{ $invoice->client->address ?? 'Alamat tidak tersedia' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($invoice->order_date)->format('d F Y') }}</p>
                    @if($invoice->due_date)<p class="mb-1"><strong>Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d F Y') }}</p>@endif
                    @if($invoice->sales)<p class="mb-1"><strong>Sales:</strong> {{ $invoice->sales->full_name }} ({{ $invoice->sales->sales_code }})</p>@endif
                    <p class="mb-1"><strong>Status:</strong>
                        @if($invoice->status == 'paid') <span class="badge bg-success fs-6">Lunas</span>
                        @elseif($invoice->status == 'partially_paid') <span class="badge bg-info text-dark fs-6">Cicil</span>
                        @elseif($invoice->status == 'cancelled') <span class="badge bg-danger fs-6">Dibatalkan</span>
                        @else <span class="badge bg-warning text-dark fs-6">Belum Lunas</span>
                        @endif
                    </p>
                </div>
            </div>
            <hr>
            
            <h5 class="fw-semibold mt-4">Rincian Invoice</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th class="text-center">Kuantitas</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Tidak ada item dalam invoice ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-7">
                    @if($invoice->notes)
                        <h6 class="fw-semibold">Catatan:</h6>
                        <p class="text-muted fst-italic bg-light p-3 rounded">{{ $invoice->notes }}</p>
                    @endif
                </div>
                <div class="col-md-5">
                    <h5 class="fw-semibold mb-3">Ringkasan Keuangan</h5>
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal Produk</span><span>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</span></div>
                        @if($invoice->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-2 text-danger"><span>Diskon ({{ $invoice->discount_percentage }}%)</span><span>(-) Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span></div>
                        @endif
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-semibold mb-2"><span>Subtotal Setelah Diskon</span><span>Rp {{ number_format($invoice->subtotal - $invoice->discount_amount, 0, ',', '.') }}</span></div>
                        @foreach($invoice->taxes as $tax)
                            <div class="d-flex justify-content-between mb-2"><span>{{ $tax->pivot->name }} ({{ $tax->pivot->rate }}%)</span><span>(+) Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</span></div>
                        @endforeach
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold"><span>Total Tagihan</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between text-success"><span>Sudah Dibayar</span><span>Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        @php
                            $totalRetur = $invoice->returns->sum('total_amount');
                            $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
                        @endphp
                        @if($totalRetur > 0)
                            <div class="d-flex justify-content-between text-warning"><span>Total Retur</span><span>(-) Rp {{ number_format($totalRetur, 0, ',', '.') }}</span></div>
                        @endif
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold fs-5 {{ $sisaTagihan > 0 ? 'text-danger' : 'text-success' }}">
                            <span>Sisa Tagihan</span>
                            <span>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($invoice->payments->isNotEmpty())
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header"><h5 class="mb-0">Riwayat Pembayaran</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead><tr><th>Tanggal</th><th>Metode</th><th class="text-end">Jumlah</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                            <td>{{ Str::title(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>{{ $payment->notes }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- MODAL PEMBAYARAN --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Catat Pembayaran untuk #{{ $invoice->invoice_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('payments.store', $invoice->invoice_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @php
                        $totalRetur = $invoice->returns->sum('total_amount');
                        $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
                    @endphp
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between"><span>Total Tagihan:</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>
                        @if($totalRetur > 0)<div class="d-flex justify-content-between"><span>Total Retur:</span><span>(-) Rp {{ number_format($totalRetur, 0, ',', '.') }}</span></div>@endif
                        <div class="d-flex justify-content-between"><span>Sudah Dibayar:</span><span>(+) Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        <hr class="my-1"><div class="d-flex justify-content-between fw-bold"><span>Sisa Tagihan:</span><span>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span></div>
                    </div>
                    <div class="mb-3">
                        <label for="amount-formatted" class="form-label">Jumlah Dibayar</label>
                        <input type="text" class="form-control" id="amount-formatted" required>
                        <input type="hidden" name="amount" id="amount">
                        <div id="amount-error" class="text-danger small mt-1"></div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Tanggal Bayar</label>
                        <input type="date" class="form-control" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="manual_transfer">Transfer Bank</option>
                            <option value="cash">Cash</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addPaymentBtn = document.getElementById('add-payment-btn');
    const amountFormattedInput = document.getElementById('amount-formatted');
    const amountHiddenInput = document.getElementById('amount');
    const amountError = document.getElementById('amount-error');

    if (amountFormattedInput) {
        const autoNumericInstance = new AutoNumeric(amountFormattedInput, { decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',' });
        if (addPaymentBtn) {
            addPaymentBtn.addEventListener('click', function() {
                const remainingBalance = {{ ($invoice->total_amount - $invoice->returns->sum('total_amount') - $invoice->amount_paid) ?? 0 }};
                autoNumericInstance.set(remainingBalance);
                amountHiddenInput.value = remainingBalance;
                autoNumericInstance.update({ maximumValue: remainingBalance });
                amountError.textContent = '';
            });

            amountFormattedInput.addEventListener('autoNumeric:rawValueModified', function(event) {
                const rawValue = event.detail.newRawValue;
                amountHiddenInput.value = rawValue;
                const remainingBalance = {{ ($invoice->total_amount - $invoice->returns->sum('total_amount') - $invoice->amount_paid) ?? 0 }};
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
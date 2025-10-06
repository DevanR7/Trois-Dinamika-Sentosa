@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Grup Tombol Aksi di Atas --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Invoice
        </a>
        <div class="d-flex flex-wrap gap-2">
            @if(!in_array($invoice->status, ['paid', 'cancelled']))
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" id="add-payment-btn">
                    <i class="bi bi-cash-coin me-1"></i> Catat Pembayaran
                </button>
            @endif
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Opsi Lain
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                     <li><a class="dropdown-item" href="{{ route('invoices.edit', $invoice->invoice_id) }}">Edit Invoice</a></li>
                     <li><hr class="dropdown-divider"></li>
                     <li><a class="dropdown-item" href="{{ route('invoices.pdf', $invoice->invoice_id) }}">Download PDF</a></li>
                     <li><hr class="dropdown-divider"></li>
                     <li>
                        <form action="{{ route('invoices.cancel', $invoice->invoice_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin membatalkan invoice ini?');">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">Batalkan Invoice</button>
                        </form>
                     </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- KARTU DETAIL INVOICE --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold">INVOICE</h2>
                    <p class="text-muted mb-0">#{{ $invoice->invoice_number }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    @if($invoice->status == 'paid') <span class="badge bg-success fs-5">LUNAS</span>
                    @elseif($invoice->status == 'partially_paid') <span class="badge bg-info fs-5">DIBAYAR SEBAGIAN</span>
                    @elseif($invoice->status == 'cancelled') <span class="badge bg-dark fs-5">DIBATALKAN</span>
                    @else <span class="badge bg-warning text-dark fs-5">BELUM LUNAS</span>
                    @endif
                    <p class="mt-2 mb-0">
                        <strong>Tanggal Pesanan:</strong> {{ optional($invoice->order_date)->format('d M Y') }}<br>
                        <strong>Tanggal Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d M Y') }}
                    </p>
                    @if($invoice->sales)
<p class="mb-0"><strong>Sales:</strong> {{ $invoice->sales->full_name }} ({{ $invoice->sales->sales_code }})</p>
@endif
                </div>
            </div>
            <hr>
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold mb-2">Untuk:</h5>
                    <p class="mb-1">
                        <strong>{{ $invoice->client->client_name ?? 'Nama Pelanggan' }}</strong><br>
                        {!! nl2br(e($invoice->client->address ?? 'Alamat Pelanggan')) !!}
                    </p>
                </div>
            </div>

            {{-- Tabel Rincian Item --}}
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Deskripsi Produk</th>
                            <th scope="col" class="text-center">Kuantitas</th>
                            <th scope="col" class="text-end">Harga Satuan</th>
                            <th scope="col" class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->product->product_name ?? 'Nama Produk' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Tidak ada item.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" rowspan="5" class="align-bottom border-0">
                                @if($invoice->notes)
                                <h6 class="fw-semibold">Catatan:</h6>
                                <p class="text-muted fst-italic">{{ $invoice->notes }}</p>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">Subtotal Produk</td>
                            <td class="text-end">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($invoice->discount_amount > 0)
                        <tr>
                            <td class="text-end fw-semibold">Diskon ({{ $invoice->discount_percentage }}%)</td>
                            <td class="text-end text-danger">(-) Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        @foreach($invoice->taxes as $tax)
                        <tr>
                            <td class="text-end fw-semibold">{{ $tax->pivot->name }} ({{ $tax->pivot->rate }}%)</td>
                            <td class="text-end">(+) Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                        <tr class="table-light">
                            <td class="text-end fw-bold fs-5">Total</td>
                            <td class="text-end fw-bold fs-5">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PEMBAYARAN --}}
    @if($invoice->payments->isNotEmpty())
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header"><h5 class="mb-0">Riwayat Pembayaran</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr><th>Tanggal</th><th>Metode</th><th class="text-end">Jumlah</th><th>Catatan</th></tr>
                    </thead>
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

{{-- MODAL UNTUK TAMBAH PEMBAYARAN --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pembayaran untuk #{{ $invoice->invoice_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('payments.store', $invoice->invoice_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        @php $sisaTagihan = $invoice->total_amount - $invoice->amount_paid; @endphp
                        <div class="d-flex justify-content-between"><span>Total Tagihan:</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between"><span>Sudah Dibayar:</span><span>Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold"><span>Sisa Tagihan:</span><span>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span></div>
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
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="manual_transfer">Transfer Bank</option>
                            <option value="cash">Cash</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (Opsional)</label>
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
        const autoNumericInstance = new AutoNumeric(amountFormattedInput, {
            decimalCharacter: ',',
            digitGroupSeparator: '.',
            currencySymbol: '',
            decimalPlaces: 0,
            minimumValue: '0'
        });

        if (addPaymentBtn) {
            addPaymentBtn.addEventListener('click', function() {
                const totalAmount = {{ $invoice->total_amount ?? 0 }};
                const amountPaid = {{ $invoice->amount_paid ?? 0 }};
                const remainingBalance = totalAmount - amountPaid;

                autoNumericInstance.set(remainingBalance);
                amountHiddenInput.value = remainingBalance;
                autoNumericInstance.update({ maximumValue: remainingBalance });
                amountError.textContent = '';
            });

            amountFormattedInput.addEventListener('autoNumeric:rawValueModified', function(event) {
                const rawValue = event.detail.newRawValue;
                amountHiddenInput.value = rawValue;
                const remainingBalance = {{ ($invoice->total_amount ?? 0) - ($invoice->amount_paid ?? 0) }};
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
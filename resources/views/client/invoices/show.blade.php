@extends('layouts.client')

@section('content')
<div class="container-fluid">
    {{-- HEADER DENGAN TOMBOL AKSI --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('client.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
        @if(in_array($invoice->status, ['unpaid', 'partially_paid']))
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="bi bi-cash-coin me-2"></i>Catat Pembayaran
            </button>
        @endif
    </div>

    {{-- KARTU DETAIL INVOICE --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            {{-- Header Invoice --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold">INVOICE</h2>
                    <p class="text-muted">#{{ $invoice->invoice_number }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($invoice->order_date)->format('d F Y') }}</p>
                    @if($invoice->due_date)<p class="mb-1"><strong>Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d F Y') }}</p>@endif
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
            
            {{-- Tabel Rincian Item --}}
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

            {{-- Bagian Catatan & Ringkasan Keuangan --}}
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
                        @php
                            $totalRetur = $invoice->returns->sum('total_amount');
                            $pendingAmount = $invoice->payments->where('status', 'pending_verification')->sum('amount');
                            $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur - $pendingAmount;
                        @endphp
                        <div class="d-flex justify-content-between fw-bold"><span>Total Tagihan</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>
                        @if($totalRetur > 0)
                            <div class="d-flex justify-content-between text-warning"><span>Total Retur</span><span>(-) Rp {{ number_format($totalRetur, 0, ',', '.') }}</span></div>
                        @endif
                        <div class="d-flex justify-content-between text-success"><span>Sudah Dibayar</span><span>(-) Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span></div>
                        @if($pendingAmount > 0)
                            <div class="d-flex justify-content-between text-info"><span>Menunggu Verifikasi</span><span>(-) Rp {{ number_format($pendingAmount, 0, ',', '.') }}</span></div>
                        @endif
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold fs-5 {{ $sisaTagihan > 0 ? 'text-danger' : 'text-success' }}">
                            <span>Sisa Tagihan</span><span>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KARTU RIWAYAT PEMBAYARAN --}}
    @if($invoice->payments->isNotEmpty())
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header"><h5 class="mb-0">Riwayat Pembayaran</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead><tr><th>Tanggal</th><th>Metode</th><th class="text-end">Jumlah</th><th>Status</th><th>Bukti</th></tr></thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                            <td>{{ Str::title(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td class="text-end">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>{{ Str::title(str_replace('_', ' ', $payment->status)) }}</td>
                            <td>
                                @if($payment->proof_of_payment_path)
                                    <a href="{{ asset('storage/' . $payment->proof_of_payment_path) }}" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- MODAL PEMBAYARAN DINAMIS --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Catat Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('client.invoices.uploadProof', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @php
                        $sisaTagihanModal = $invoice->total_amount - $invoice->amount_paid - $invoice->returns->sum('total_amount') - $invoice->payments->where('status', 'pending_verification')->sum('amount');
                    @endphp
                    <div class="alert alert-info">Sisa Tagihan: <strong class="fs-5">Rp {{ number_format($sisaTagihanModal, 0, ',', '.') }}</strong></div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Metode --</option>
                            <option value="cash">Cash (via Sales)</option>
                            <option value="manual_transfer">Transfer Bank</option>
                        </select>
                    </div>
                    <div id="cash-fields" class="d-none">
                        <div class="mb-3">
                            <label for="user_id_sales" class="form-label">Diterima oleh Sales</label>
                            <select name="user_id_sales" id="user_id_sales" class="form-select">
                                <option value="" disabled selected>-- Pilih Sales --</option>
                                @foreach($salesUsers as $sales)
                                    <option value="{{ $sales->user_id }}">{{ $sales->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="transfer-fields" class="d-none">
                        <div class="mb-3">
                            <label for="proof_of_payment" class="form-label">File Bukti Bayar (JPG, PNG)</label>
                            <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment" accept="image/jpeg,image/png">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_amount_display" class="form-label">Jumlah Dibayar</label>
                        <input type="text" class="form-control" id="payment_amount_display" placeholder="Rp 0" required>
                        <input type="hidden" name="payment_amount" id="payment_amount">
                        <div id="amount-error" class="text-danger small mt-1 d-none">Jumlah tidak boleh melebihi sisa tagihan.</div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submit-proof-btn">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Skrip untuk Modal Dinamis
    const paymentMethodSelect = document.getElementById('payment_method');
    const cashFields = document.getElementById('cash-fields');
    const transferFields = document.getElementById('transfer-fields');
    const proofInput = document.getElementById('proof_of_payment');
    const salesInput = document.getElementById('user_id_sales');

    paymentMethodSelect.addEventListener('change', function() {
        if (this.value === 'cash') {
            cashFields.classList.remove('d-none');
            transferFields.classList.add('d-none');
            proofInput.required = false;
            salesInput.required = true;
        } else if (this.value === 'manual_transfer') {
            cashFields.classList.add('d-none');
            transferFields.classList.remove('d-none');
            proofInput.required = true;
            salesInput.required = false;
        } else {
            cashFields.classList.add('d-none');
            transferFields.classList.add('d-none');
            proofInput.required = false;
            salesInput.required = false;
        }
    });

    // Skrip untuk Format Rupiah dan Validasi
    const amountDisplay = document.getElementById('payment_amount_display');
    const amountRaw = document.getElementById('payment_amount');
    const sisaTagihan = {{ $sisaTagihanModal }};
    const amountError = document.getElementById('amount-error');
    const submitBtn = document.getElementById('submit-proof-btn');

    amountDisplay.addEventListener('input', function(e) {
        let rawValue = e.target.value.replace(/[^0-9]/g, '');
        let numericValue = rawValue ? parseInt(rawValue, 10) : 0;
        
        // Tampilkan input pengguna apa adanya
        e.target.value = numericValue > 0 ? 'Rp ' + numericValue.toLocaleString('id-ID') : '';
        
        // Validasi setelah menampilkan
        if (numericValue > sisaTagihan) {
            amountError.classList.remove('d-none');
            submitBtn.disabled = true;
            amountRaw.value = sisaTagihan;
        } else {
            amountError.classList.add('d-none');
            submitBtn.disabled = false;
            amountRaw.value = numericValue;
        }
    });
});
</script>
@endpush
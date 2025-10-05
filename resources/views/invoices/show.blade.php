@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Tombol Aksi di Atas --}}
    <div class="d-flex justify-content-end mb-3 gap-2">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="bi bi-printer me-2"></i>Cetak
        </button>
        <a href="{{ route('invoices.pdf', $invoice->invoice_id) }}" class="btn btn-primary">
            <i class="bi bi-download me-2"></i>Download PDF
        </a>

        @if(!in_array($invoice->status, ['paid', 'cancelled']))
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
    <i class="bi bi-cash-coin me-2"></i>Catat Pembayaran
</button>
@endif
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            {{-- Header Invoice --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold">INVOICE</h2>
                    <p class="text-muted">#{{ $invoice->invoice_number ?? 'INV-0001' }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    @if($invoice->status == 'paid')
    <span class="badge bg-success fs-6">LUNAS</span>
@elseif($invoice->status == 'partially_paid')
    <span class="badge bg-info fs-6">DIBAYAR SEBAGIAN</span>
@elseif($invoice->status == 'unpaid')
    <span class="badge bg-warning text-dark fs-6">BELUM LUNAS</span>
@else {{-- Ini akan menangani status 'cancelled' dan lainnya --}}
    <span class="badge bg-secondary fs-6">{{ Str::title($invoice->status) }}</span>
@endif
                    <p class="mt-2">
                        <strong>Tanggal Terbit:</strong> {{ optional($invoice->invoice_date)->format('d M Y') ?? 'N/A' }}<br>
                        <strong>Tanggal Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d M Y') ?? 'N/A' }}
                    </p>
                </div>
            </div>

            <hr>

            {{-- Informasi Perusahaan dan Pelanggan --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold mb-3">Dari:</h5>
                    <p class="mb-1">
                        <strong>Nama Perusahaan Anda</strong><br>
                        Jalan Teknologi No. 123<br>
                        Semarang, Indonesia<br>
                        info@perusahaananda.com
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-semibold mb-3">Untuk:</h5>
                    <p class="mb-1">
                        <strong>{{ $invoice->client->client_name ?? 'Nama Pelanggan' }}</strong><br>
                        {{ $invoice->client->address ?? 'Alamat Pelanggan' }}<br>
                        Kontak: {{ $invoice->client->person_in_charge ?? '' }}<br>
                        Email: {{ $invoice->client->email ?? 'email@pelanggan.com' }}
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
                        @forelse($invoice->items as $index => $item)
                        <tr>
                            <th scope="row">{{ $index + 1 }}</th>
                            <td>{{ $item->product->product_name ?? 'Nama Produk' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada item.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    {{-- BAGIAN YANG DIPERBAIKI --}}
                    
    <tr>
        <td colspan="3"></td>
        <td class="text-end fw-semibold">Subtotal</td>
        <td class="text-end">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
    </tr>

    {{-- Loop untuk setiap pajak yang diterapkan pada invoice ini --}}
    @foreach($invoice->taxes as $tax)
    <tr>
        <td colspan="3"></td>
        <td class="text-end fw-semibold">{{ $tax->pivot->name }} ({{ $tax->pivot->rate }}%)</td>
        <td class="text-end">Rp {{ number_format($tax->pivot->amount, 0, ',', '.') }}</td>
    </tr>
    @endforeach

    <tr class="table-light">
        <td colspan="3"></td>
        <td class="text-end fw-bold fs-5">Total</td>
        <td class="text-end fw-bold fs-5">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
    </tr>
</tfoot>
                </table>
            </div>

            {{-- Catatan / Terms --}}
            <div class="mt-4">
                <h6 class="fw-semibold">Catatan:</h6>
                <p class="text-muted">Terima kasih telah melakukan bisnis dengan kami. Mohon lakukan pembayaran sebelum tanggal jatuh tempo.</p>
            </div>
        </div>
    </div>
</div>
@endsection

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Catat Pembayaran untuk #{{ $invoice->invoice_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="invoice_id" value="{{ $invoice->invoice_id }}">
                <div class="modal-body">

                    <div class="alert alert-info">
                        @php
                            $sisaTagihan = $invoice->total_amount - $invoice->amount_paid;
                        @endphp
                        <strong>Total Tagihan:</strong> Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}<br>
                        <strong>Sudah Dibayar:</strong> Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}<br>
                        <hr>
                        <strong class="fs-5">Sisa Tagihan: Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</strong>
                    </div>

                    <div class="mb-3">
    <label for="amount_display" class="form-label">Jumlah Dibayar</label>
    {{-- Input ini yang dilihat user --}}
    <input type="text" class="form-control" id="amount_display" placeholder="Rp 0">
    {{-- Input ini yang dikirim ke server (angka murni) --}}
    <input type="hidden" name="amount" id="amount">
</div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Tanggal Bayar</label>
                        <input type="date" class="form-control" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Transfer Bank</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (Opsional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const amountDisplay = document.getElementById('amount_display');
        const amountRaw = document.getElementById('amount');

        amountDisplay.addEventListener('input', function(e) {
            // 1. Ambil nilai, hapus semua kecuali angka
            let rawValue = e.target.value.replace(/[^0-9]/g, '');

            // 2. Simpan angka murni di input hidden
            amountRaw.value = rawValue;

            // 3. Format angka dengan titik, lalu tampilkan
            if (rawValue) {
                e.target.value = 'Rp ' + parseInt(rawValue, 10).toLocaleString('id-ID');
            } else {
                e.target.value = '';
            }
        });
    });
</script>
@endpush
@extends('layouts.client')

@section('content')
<div class="container-fluid">
    {{-- HEADER DENGAN TOMBOL AKSI --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <a href="{{ route('client.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>

        <div class="d-flex flex-wrap gap-2">
        @php
            // Hitung total tagihan efektif setelah dikurangi retur
            $totalRetur = $invoice->returns->sum('total_amount');
            $effectiveBill = $invoice->total_amount - $totalRetur;

            // Hitung total pembayaran "potensial", yaitu yang sudah lunas + yang sedang diverifikasi
            $pendingAmount = $invoice->payments->where('status', 'pending_verification')->sum('amount');
            $potentialPaid = $invoice->amount_paid + $pendingAmount;

            // Tombol bayar hanya muncul jika pembayaran potensial MASIH KURANG dari tagihan efektif
            $canPay = $potentialPaid < $effectiveBill;
        @endphp

        @if(in_array($invoice->status, ['unpaid', 'partially_paid']) && $canPay)
            {{-- TOMBOL UTAMA BARU YANG DIGABUNG --}}
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#paymentMethodModal">
                <i class="bi bi-credit-card-fill me-2"></i> Bayar Tagihan
            </button>
        @endif
    </div>
</div>

    {{-- KARTU DETAIL INVOICE (Tidak ada perubahan di sini, sama seperti kode Anda) --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            {{-- Header Invoice --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold mb-1">INVOICE</h2>
                    <p class="text-muted">#{{ $invoice->invoice_number }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Tanggal Pesanan:</strong> {{ optional($invoice->order_date)->format('d F Y') }}</p>
                    @if($invoice->due_date)
                        <p class="mb-1"><strong>Jatuh Tempo:</strong> {{ optional($invoice->due_date)->format('d F Y') }}</p>
                    @endif
                    <p class="mb-1">
                        <strong>Status:</strong>
                        @if($invoice->status == 'paid')
                            <span class="badge bg-success fs-6">Lunas</span>
                        @elseif($invoice->status == 'partially_paid')
                            <span class="badge bg-info text-dark fs-6">Cicil</span>
                        @elseif($invoice->status == 'cancelled')
                            <span class="badge bg-danger fs-6">Dibatalkan</span>
                        @else
                            <span class="badge bg-warning text-dark fs-6">Belum Lunas</span>
                        @endif
                    </p>
                </div>
            </div>
            <hr>

            {{-- Rincian Item --}}
            <h5 class="fw-semibold mt-4">Rincian Invoice</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
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
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada item dalam invoice ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Catatan dan Ringkasan Keuangan --}}
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
                            $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
                            $sisaTagihanTampil = $sisaTagihan - $pendingAmount;
                        @endphp
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total Tagihan</span>
                            <span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                        </div>
                        @if($totalRetur > 0)
                            <div class="d-flex justify-content-between text-warning">
                                <span>Total Retur</span><span>(-) Rp {{ number_format($totalRetur, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between text-success">
                            <span>Sudah Dibayar</span><span>(-) Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</span>
                        </div>
                        @if($pendingAmount > 0)
                            <div class="d-flex justify-content-between text-info">
                                <span>Menunggu Verifikasi</span><span>(-) Rp {{ number_format($pendingAmount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold fs-5 {{ $sisaTagihanTampil > 0 ? 'text-danger' : 'text-success' }}">
                            <span>Sisa Tagihan</span>
                            <span>Rp {{ number_format($sisaTagihanTampil, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PEMBAYARAN --}}
    @if($invoice->payments->isNotEmpty())
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-light"><h5 class="mb-0 fw-semibold">Riwayat Pembayaran</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th class="text-end">Jumlah</th>
                            <th>Status</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
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
                                        <span class="text-muted">-</span>
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
@php
    // Definisikan sisa tagihan di sini agar bisa diakses semua modal
    $remainingBalance = $invoice->total_amount - $invoice->amount_paid - $invoice->returns->sum('total_amount');
@endphp

<div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Metode Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Silakan pilih cara Anda ingin membayar tagihan ini:</p>
                <div class="d-grid gap-3">
                    <button class="btn btn-outline-primary p-3" id="pay-manual-transfer-btn">
                        <i class="bi bi-bank2 fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">Transfer Bank</span><br>
                            <small>Upload bukti transfer manual.</small>
                        </div>
                    </button>
                    <button class="btn btn-outline-success p-3" id="pay-cash-btn">
                        <i class="bi bi-person-check-fill fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">Cash (via Sales)</span><br>
                            <small>Pembayaran tunai melalui tim sales.</small>
                        </div>
                    </button>
                    <button class="btn btn-outline-dark p-3" id="pay-online-btn">
                        <i class="bi bi-credit-card-2-front-fill fs-4 me-2"></i>
                        <div>
                            <span class="fw-bold">Pembayaran Online</span><br>
                            <small>Kartu Kredit, Virtual Account, dll.</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="manualPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manualPaymentModalTitle">Catat Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('client.invoices.uploadProof', $invoice->invoice_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="payment_method" id="payment_method_input">
                <div class="modal-body">
                    <div class="alert alert-info">
                        Sisa Tagihan: <strong class="fs-5">Rp {{ number_format($remainingBalance, 0, ',', '.') }}</strong>
                    </div>

                    {{-- Bagian ini hanya untuk 'Cash via Sales' --}}
                    <div id="cash-fields" class="d-none">
                        <div class="mb-3">
                            <label for="user_id_sales" class="form-label">Diterima oleh Sales <span class="text-danger">*</span></label>
                            <select name="user_id_sales" id="user_id_sales" class="form-select">
                                <option value="" disabled selected>-- Pilih Sales --</option>
                                @foreach($salesUsers as $sales)
                                    <option value="{{ $sales->user_id }}">{{ $sales->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Bagian ini hanya untuk 'Transfer Bank' --}}
                    <div id="transfer-fields" class="d-none">
                        <div class="mb-3">
                            <label for="proof_of_payment" class="form-label">File Bukti Bayar (JPG, PNG) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="proof_of_payment" id="proof_of_payment" accept="image/jpeg,image/png">
                        </div>
                    </div>

                    {{-- Bagian umum untuk keduanya --}}
                    <div class="mb-3">
                        <label for="payment_amount_display" class="form-label">Jumlah Dibayar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="payment_amount_display" placeholder="Rp 0">
                        <input type="hidden" name="payment_amount" id="payment_amount">
                        <div id="amount-error" class="text-danger small mt-1 d-none">Jumlah tidak boleh melebihi sisa tagihan.</div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (Opsional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submit-proof-btn" disabled>Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="midtransPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pembayaran Online Invoice #{{ $invoice->invoice_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('client.invoices.pay', $invoice->invoice_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @php
                        // Gunakan perhitungan yang sama dengan modal manual untuk konsistensi
                        $totalRetur = $invoice->returns->sum('total_amount');
                        $pendingPayments = $invoice->payments->where('status', 'pending_verification')->sum('amount');
                        $sisaTagihanMidtrans = $invoice->total_amount - $invoice->amount_paid - $totalRetur - $pendingPayments;
                    @endphp
                    <div class="alert alert-info">
                        Sisa Tagihan: <strong class="fs-5">Rp {{ number_format($sisaTagihanMidtrans, 0, ',', '.') }}</strong>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Jumlah yang Ingin Dibayar</label>
                        <input type="number" class="form-control" name="amount" id="amount" value="{{ $sisaTagihanMidtrans }}" max="{{ $sisaTagihanMidtrans }}" min="1" required>
                        <small class="text-muted">Anda bisa membayar lunas atau mencicil.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Lanjutkan ke Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('scripts')
{{-- Library untuk format angka --}}
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

{{-- Script Midtrans Snap --}}
<script type="text/javascript"
    src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('midtrans.client_key') }}"></script>

{{-- SCRIPT BARU UNTUK MENGATUR LOGIKA MODAL DAN VALIDASI --}}
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi semua modal Bootstrap
        const paymentMethodModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));
        const manualPaymentModal = new bootstrap.Modal(document.getElementById('manualPaymentModal'));
        const midtransPaymentModal = new bootstrap.Modal(document.getElementById('midtransPaymentModal'));

        // Elemen-elemen dalam form manual
        const manualPaymentForm = document.querySelector('#manualPaymentModal form');
        const titleEl = document.getElementById('manualPaymentModalTitle');
        const methodInput = document.getElementById('payment_method_input');
        const cashFields = document.getElementById('cash-fields');
        const transferFields = document.getElementById('transfer-fields');
        const salesSelect = document.getElementById('user_id_sales');
        const proofInput = document.getElementById('proof_of_payment');
        const submitBtn = document.getElementById('submit-proof-btn');

        // Elemen untuk input jumlah
        const amountDisplay = document.getElementById('payment_amount_display');
        const amountHidden = document.getElementById('payment_amount');
        const amountError = document.getElementById('amount-error');
        const remainingBalance = parseFloat("{{ $remainingBalance }}");

        // Inisialisasi AutoNumeric pada input jumlah
        const autoNumericInstance = new AutoNumeric(amountDisplay, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            currencySymbol: 'Rp ',
            currencySymbolPlacement: 'p',
            minimumValue: 0,
            maximumValue: remainingBalance // Batasi nilai maksimum
        });
        
        // Atur nilai default ke sisa tagihan saat modal dibuka
        autoNumericInstance.set(remainingBalance);
        amountHidden.value = remainingBalance;
        validateAmount(); // Langsung validasi saat pertama kali dibuka

        // Fungsi untuk validasi jumlah
        function validateAmount() {
            const rawValue = autoNumericInstance.getNumericString();
            amountHidden.value = rawValue;

            const isValid = rawValue && parseFloat(rawValue) > 0 && parseFloat(rawValue) <= remainingBalance;

            if (!isValid && rawValue !== '') {
                amountError.classList.remove('d-none');
            } else {
                amountError.classList.add('d-none');
            }
            
            // Tombol submit hanya aktif jika jumlah valid
            submitBtn.disabled = !isValid;
        }

        // Event listener untuk input jumlah
        amountDisplay.addEventListener('keyup', validateAmount);
        amountDisplay.addEventListener('change', validateAmount);


        // --- LOGIKA PEMILIHAN METODE PEMBAYARAN ---

        // 1. Klien memilih "Transfer Bank"
        document.getElementById('pay-manual-transfer-btn').addEventListener('click', function() {
            paymentMethodModal.hide();

            // Atur form untuk transfer
            titleEl.textContent = 'Konfirmasi Pembayaran Transfer Bank';
            methodInput.value = 'manual_transfer';
            cashFields.classList.add('d-none');
            transferFields.classList.remove('d-none');
            proofInput.required = true;
            salesSelect.required = false;

            manualPaymentModal.show();
        });

        // 2. Klien memilih "Cash (via Sales)"
        document.getElementById('pay-cash-btn').addEventListener('click', function() {
            paymentMethodModal.hide();
            
            // Atur form untuk cash
            titleEl.textContent = 'Konfirmasi Pembayaran Cash';
            methodInput.value = 'cash';
            transferFields.classList.add('d-none');
            cashFields.classList.remove('d-none');
            salesSelect.required = true;
            proofInput.required = false;

            manualPaymentModal.show();
        });

        // 3. Klien memilih "Pembayaran Online"
        document.getElementById('pay-online-btn').addEventListener('click', function() {
            paymentMethodModal.hide();
            midtransPaymentModal.show();
        });


        // --- LOGIKA SUBMIT MIDTRANS (Sama seperti kode Anda, hanya diperbaiki sedikit) ---
        const midtransForm = document.querySelector('#midtransPaymentModal form');
        if (midtransForm) {
            midtransForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const formData = new FormData(this);
                const payButton = this.querySelector('button[type="submit"]');
                payButton.disabled = true;
                payButton.innerHTML = 'Memproses...';

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.snap_token) {
                        window.snap.pay(data.snap_token, {
                            onSuccess: function(result){ window.location.reload(); },
                            onPending: function(result){ window.location.reload(); },
                            onError: function(result){ 
                                alert("Pembayaran gagal!");
                                payButton.disabled = false;
                                payButton.innerHTML = 'Lanjutkan ke Pembayaran';
                            },
                            onClose: function(){
                                payButton.disabled = false;
                                payButton.innerHTML = 'Lanjutkan ke Pembayaran';
                            }
                        });
                    } else {
                        throw new Error(data.message || 'Gagal mendapatkan token pembayaran.');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                    payButton.disabled = false;
                    payButton.innerHTML = 'Lanjutkan ke Pembayaran';
                });
            });
        }
    });
</script>
@endpush
@extends('layouts.app')

{{-- Menambahkan Stylesheet untuk Select2 --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Catat Pembayaran Klien (Batch) 💶</h4>
                </div>
                <div class="card-body p-4">

                    {{-- Menampilkan Error Validasi atau Session Error --}}
                    @if ($errors->any() || session('error'))
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            @if (session('error'))<li>{{ session('error') }}</li>@endif
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('batch-payments.store') }}" method="POST" id="batch-payment-form">
                        @csrf
                        {{-- Bagian 1: Pilih Klien --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label fw-semibold">1. Pilih Klien</label>
                                <select name="client_id" id="client_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Klien --</option>
                                    @foreach ($clients as $client)
                                    <option value="{{ $client->client_id }}">{{ $client->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- =================================== --}}
                            {{-- ✅ AREA SALDO KLIEN YANG DIPERBARUI --}}
                            {{-- =================================== --}}
                            <div class="col-md-6 align-self-end">
                                <div id="client-credit-info" class="alert alert-info py-2 d-none">
                                    Saldo Tersedia: <strong id="client-credit-balance">Rp 0</strong>
                                    <br>
                                    <small class="text-muted">Saldo Tertahan: <strong id="client-pending-balance">Rp 0</strong></small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Bagian 2: Detail Pembayaran --}}
                        <h5 class="fw-semibold">2. Detail Pembayaran</h5>
                        <div class="row mb-3 g-3">
                             {{-- Checkbox Baru: Gunakan Saldo Kredit --}}
                            <div class="col-12 mb-2">
                                <div class="form-check form-switch" id="use-credit-container" style="display: none;">
                                    <input class="form-check-input" type="checkbox" role="switch" id="use_credit" name="use_credit" value="1">
                                    <label class="form-check-label" for="use_credit">Gunakan Saldo Kredit untuk pembayaran ini?</label>
                                </div>
                            </div>
                            {{-- Input Dana Diterima (Non-Kredit) --}}
                            <div class="col-md-4">
                                <label for="total_amount_formatted" class="form-label">Total Dana Diterima (Non-Kredit)</label>
                                <input type="text" class="form-control" id="total_amount_formatted">
                                <input type="hidden" name="total_amount" id="total_amount" value="0">
                            </div>
                            {{-- Input Tanggal Bayar --}}
                            <div class="col-md-4">
                                <label for="payment_date" class="form-label">Tanggal Bayar</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            {{-- Input Metode Bayar --}}
                            <div class="col-md-4">
                                <label for="payment_method" class="form-label">Metode Bayar (Non-Kredit)</label>
                                <select name="payment_method" id="payment_method" class="form-select">
                                     <option value="">-- Pilih Metode --</option>
                                     <option value="manual_transfer">Transfer Bank</option>
                                     <option value="cash">Cash</option>
                                     <option value="other">Lainnya</option>
                                </select>
                            </div>
                            {{-- Input Catatan --}}
                            <div class="col-12">
                                <label for="notes" class="form-label">Catatan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <hr>

                        {{-- Bagian 3: Alokasi ke Invoice --}}
                        <h5 class="fw-semibold">3. Alokasi ke Invoice (Diurutkan dari paling lama)</h5>
                        <p class="text-muted small">Pilih invoice yang akan dibayar. Sistem akan melunasi invoice dari urutan teratas (paling lama) terlebih dahulu menggunakan saldo kredit (jika dipilih) dan dana diterima.</p>
                        <div id="invoice-list-container" class="border rounded p-3" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                            <p class="text-center text-muted" id="invoice-placeholder">Silakan pilih klien untuk melihat daftar invoice.</p>
                            <table class="table table-sm table-hover d-none" id="invoice-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="check-all-invoices" class="form-check-input"></th>
                                        <th>No. Invoice</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-end">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-list-body">
                                    {{-- Daftar invoice akan di-load di sini oleh Javascript --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Footer: Total Dipilih dan Tombol Simpan --}}
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 mt-3 rounded">
                            <h5 class="mb-0">Total Tagihan Dipilih: <span id="total-selected-display" class="fw-bold text-danger">Rp 0</span></h5>
                            <button type="submit" class="btn btn-success btn-lg">Simpan Pembayaran Batch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Include Select2 & AutoNumeric JS --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi Elemen DOM
    const clientSelect = $('#client_id');
    const invoiceTable = document.getElementById('invoice-table');
    const invoiceListBody = document.getElementById('invoice-list-body');
    const invoicePlaceholder = document.getElementById('invoice-placeholder');
    const checkAll = document.getElementById('check-all-invoices');
    const totalSelectedDisplay = document.getElementById('total-selected-display');
    const amountFormattedInput = document.getElementById('total_amount_formatted');
    const amountHiddenInput = document.getElementById('total_amount');
    const creditInfoDiv = document.getElementById('client-credit-info');
    const creditBalanceSpan = document.getElementById('client-credit-balance');
    const pendingBalanceSpan = document.getElementById('client-pending-balance'); // ✅ BARU
    const useCreditContainer = document.getElementById('use-credit-container');
    const useCreditCheckbox = document.getElementById('use_credit');
    const paymentMethodSelect = document.getElementById('payment_method');
    let currentCreditBalance = 0;

    // Inisialisasi AutoNumeric untuk input jumlah
    const autoNumericInstance = new AutoNumeric(amountFormattedInput, {
        decimalPlaces: 0,
        digitGroupSeparator: '.',
        decimalCharacter: ',',
        minimumValue: 0 // Pastikan minimal 0
    });
    // Update hidden input saat nilai berubah
    amountFormattedInput.addEventListener('autoNumeric:rawValueModified', (e) => {
        amountHiddenInput.value = e.detail.newRawValue || 0; // Default ke 0 jika kosong
        toggleRequiredFields(); // Panggil toggle saat amount berubah
    });

    // Inisialisasi Select2 untuk dropdown klien
    clientSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari Klien --'
    });

    // Fungsi helper format Rupiah
    function formatRupiah(number) {
        if (isNaN(number)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency', currency: 'IDR', minimumFractionDigits: 0
        }).format(number);
    }

    // Fungsi untuk menghitung total sisa tagihan dari invoice yang dipilih
    function calculateTotalSelected() {
        let total = 0;
        document.querySelectorAll('.invoice-checkbox:checked').forEach(checkbox => {
            total += parseFloat(checkbox.dataset.balance || 0); // Ambil dari data-balance
        });
        totalSelectedDisplay.textContent = formatRupiah(total);
        // Panggil toggle required karena total terpilih mungkin berubah
        toggleRequiredFields();
    }

    // Fungsi untuk mengatur required dan state input/select
    function toggleRequiredFields() {
        // Ambil nilai numerik total tagihan terpilih
        const selectedInvoicesBalanceString = totalSelectedDisplay.textContent || 'Rp 0';
        const selectedInvoicesBalance = parseFloat(selectedInvoicesBalanceString.replace(/[^0-9,-]+/g,"").replace(",", ".")) || 0;

        const useCreditIsChecked = useCreditCheckbox.checked;
        const inputAmountValue = parseFloat(amountHiddenInput.value || 0);
        const creditIsSufficient = currentCreditBalance >= selectedInvoicesBalance && selectedInvoicesBalance > 0;

        if (useCreditIsChecked) {
            if (creditIsSufficient) {
                // Kredit cukup: dana input tidak perlu & nonaktif
                if (!amountFormattedInput.disabled) { // Hanya set ke 0 jika sebelumnya aktif
                    autoNumericInstance.set(0);
                }
                amountFormattedInput.required = false;
                amountFormattedInput.disabled = true;
                paymentMethodSelect.required = false;
                paymentMethodSelect.disabled = true; // Nonaktifkan juga metode
                paymentMethodSelect.value = ""; // Kosongkan
            } else {
                // Kredit kurang: dana input perlu & aktif
                amountFormattedInput.required = true; // Wajib input kekurangan
                amountFormattedInput.disabled = false;
                paymentMethodSelect.required = true; // Metode juga wajib
                paymentMethodSelect.disabled = false;
                if (!paymentMethodSelect.value) paymentMethodSelect.value = "manual_transfer"; // Default jika kosong
            }
        } else {
            // Tidak pakai kredit: dana input perlu & aktif
            amountFormattedInput.required = true;
            amountFormattedInput.disabled = false;
            // Metode wajib jika ada dana input > 0
            paymentMethodSelect.required = inputAmountValue > 0;
            paymentMethodSelect.disabled = false;
             if (paymentMethodSelect.required && !paymentMethodSelect.value) {
                paymentMethodSelect.value = "manual_transfer";
            } else if (!paymentMethodSelect.required) {
                 paymentMethodSelect.value = ""; // Kosongkan jika dana input 0
            }
        }
    }

    // Event listener untuk checkbox 'Use Credit'
     useCreditCheckbox.addEventListener('change', toggleRequiredFields);

    // Event listener untuk checkbox 'Check All'
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.invoice-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        calculateTotalSelected(); // Hitung ulang total & panggil toggleRequired
    });

    // Fungsi untuk menambahkan event listener ke checkbox invoice individual
    function addInvoiceCheckboxListeners() {
        document.querySelectorAll('.invoice-checkbox').forEach(cb => {
            // Hapus listener lama jika ada (mencegah duplikasi)
            cb.removeEventListener('change', handleInvoiceCheckboxChange);
            // Tambah listener baru
            cb.addEventListener('change', handleInvoiceCheckboxChange);
        });
    }

    // Handler untuk perubahan checkbox invoice individual
    function handleInvoiceCheckboxChange() {
        calculateTotalSelected(); // calculateTotalSelected sudah memanggil toggleRequiredFields
    }


    // Event listener utama saat klien dipilih
    clientSelect.on('change', async function () {
        const clientId = this.value;

        // Reset tampilan & state sebelum fetch data baru
        creditInfoDiv.classList.add('d-none');
        useCreditContainer.style.display = 'none';
        useCreditCheckbox.checked = false;
        currentCreditBalance = 0;
        invoicePlaceholder.textContent = 'Memuat data...';
        invoiceTable.classList.add('d-none');
        invoiceListBody.innerHTML = '';
        checkAll.checked = false;
        autoNumericInstance.set(0); // Reset input amount jadi 0

        if (!clientId) {
            invoicePlaceholder.textContent = 'Silakan pilih klien untuk melihat daftar invoice.';
            calculateTotalSelected(); // Reset total terpilih jadi 0
            toggleRequiredFields(); // Panggil untuk reset required
            return; // Hentikan jika tidak ada klien dipilih
        }

        try {
            // =================================== --}}
            // ✅ PERBAIKAN FETCH API (Menggunakan 'balance' dan 'pending_balance') --}}
            // =================================== --}}
            const clientResponse = await fetch(`/api/clients/${clientId}/details`);
            if (!clientResponse.ok) throw new Error('Gagal ambil data klien');
            const clientData = await clientResponse.json();
            
            // Gunakan 'balance' (available)
            currentCreditBalance = parseFloat(clientData.balance) || 0; 
            const pendingBalance = parseFloat(clientData.pending_balance) || 0;

            creditBalanceSpan.textContent = formatRupiah(currentCreditBalance);
            pendingBalanceSpan.textContent = formatRupiah(pendingBalance);
            creditInfoDiv.classList.remove('d-none'); // Selalu tampilkan info, meski 0

            if (currentCreditBalance > 0) {
                useCreditContainer.style.display = 'block'; // Tampilkan checkbox
            }
            // =================================== --}}

            // Fetch Invoices (API Anda sudah diperbarui di langkah sebelumnya)
            const invoiceResponse = await fetch(`/api/clients/${clientId}/unpaid-invoices`);
            if (!invoiceResponse.ok) throw new Error('Gagal mengambil data invoice');
            const invoices = await invoiceResponse.json();

            // Tampilkan daftar invoice atau pesan jika kosong
            if (invoices.length === 0) {
                invoicePlaceholder.textContent = 'Klien ini tidak memiliki tagihan.';
            } else {
                invoices.forEach(invoice => {
                    // Buat baris tabel untuk setiap invoice
                    const row = `
                        <tr>
                            <td>
                                <input class="form-check-input invoice-checkbox"
                                       type="checkbox"
                                       name="invoice_ids[]"
                                       value="${invoice.invoice_id}"
                                       data-balance="${invoice.sisa_tagihan}">
                            </td>
                            <td>${invoice.invoice_number}</td>
                            <td>${invoice.due_date_formatted}</td>
                            <td class="text-end">${formatRupiah(invoice.sisa_tagihan)}</td>
                        </tr>
                    `;
                    // Masukkan baris ke dalam tbody
                    invoiceListBody.insertAdjacentHTML('beforeend', row);
                });
                invoicePlaceholder.textContent = ''; // Hapus pesan placeholder
                invoiceTable.classList.remove('d-none'); // Tampilkan tabel

                // Tambahkan event listener ke setiap checkbox invoice baru
                addInvoiceCheckboxListeners();
            }

        } catch (error) {
            // Tangani error jika fetch gagal
            invoicePlaceholder.textContent = 'Gagal memuat data. Silakan coba lagi.';
            console.error('Error fetching data:', error);
            // Sembunyikan info kredit jika terjadi error
            creditInfoDiv.classList.add('d-none');
            useCreditContainer.style.display = 'none';
        }

        calculateTotalSelected(); // Hitung total terpilih (awalnya mungkin 0)
        toggleRequiredFields(); // Panggil setelah data client dan invoice ter-load
    });

    // Panggil sekali di awal untuk set state required awal
    toggleRequiredFields();
});
</script>
@endpush
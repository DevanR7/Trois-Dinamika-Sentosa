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
                <div class="card-header bg-danger text-white"> {{-- Ubah warna jadi merah untuk hutang --}}
                    <h4 class="mb-0">Catat Pembayaran Hutang (Batch) 💸</h4>
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

                    <form action="{{ route('batch-purchase-payments.store') }}" method="POST" id="batch-payment-form">
                        @csrf
                        {{-- Bagian 1: Pilih Supplier --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label fw-semibold">1. Pilih Supplier</label>
                                <select name="supplier_id" id="supplier_id" class="form-select" required>
                                    <option value="" disabled selected>-- Cari Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Area Baru: Menampilkan Saldo Deposit --}}
                            <div class="col-md-6 align-self-end">
                                <div id="supplier-debit-info" class="alert alert-info py-2 d-none">
                                    Saldo Tersedia: <strong id="supplier-debit-balance">Rp 0</strong>
                                    {{-- ✅ TAMBAHAN: Tampilkan Saldo Tertahan --}}
                                    <br>
                                    <small class="text-muted">Saldo Tertahan: <strong id="supplier-pending-balance">Rp 0</strong></small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Bagian 2: Detail Pembayaran --}}
                        <h5 class="fw-semibold">2. Detail Pembayaran</h5>
                        <div class="row mb-3 g-3">
                             {{-- Checkbox Baru: Gunakan Saldo Deposit --}}
                            <div class="col-12 mb-2">
                                <div class="form-check form-switch" id="use-debit-container" style="display: none;">
                                    <input class="form-check-input" type="checkbox" role="switch" id="use_debit_balance" name="use_debit_balance" value="1">
                                    <label class="form-check-label" for="use_debit_balance">Gunakan Saldo Deposit untuk pembayaran ini?</label>
                                </div>
                            </div>
                            {{-- Input Dana Dibayar (Non-Deposit) --}}
                            <div class="col-md-4">
                                <label for="total_amount_formatted" class="form-label">Total Dana Dibayar (Non-Deposit)</label>
                                <input type="text" class="form-control" id="total_amount_formatted">
                                <input type="hidden" name="total_amount" id="total_amount" value="0">
                            </div>
                            {{-- Input Tanggal Bayar --}}
                            <div class="col-md-4">
                                <label for="payment_date" class="form-label">Tanggal Bayar</label>
                                <input type="date" class="form-control" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            
                            {{-- =================================== --}}
                            {{-- ✅ PERBAIKAN: Dropdown Metode Pembayaran --}}
                            {{-- =================================== --}}
                            <div class="col-md-4">
                                <label for="payment_method_id" class="form-label">Metode Bayar (Non-Deposit)</label>
                                <select name="payment_method_id" id="payment_method_id" class="form-select">
                                     <option value="">-- Pilih Metode --</option>
                                     {{-- Loop dari variabel controller --}}
                                     @foreach ($paymentMethods as $method)
                                     <option value="{{ $method->payment_method_id }}" data-type="{{ $method->type }}">
                                         {{ $method->name }}
                                     </option>
                                     @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
    <label for="company_bank_account_id_batch" class="form-label">Setor ke Akun <span class="text-danger">*</span></label>
    <select name="company_bank_account_id" id="company_bank_account_id_batch" class="form-select">
        <option value="">-- Pilih Akun Bank/Kas --</option>
        @foreach($companyBankAccounts as $account)
            <option value="{{ $account->company_bank_account_id }}">
                {{ $account->bank_name }} - {{ $account->account_number ?? $account->account_name }}
            </option>
        @endforeach
    </select>
</div>
                            
                            {{-- Input Catatan --}}
                            <div class="col-md-4">
                                <label for="notes" class="form-label">Catatan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <hr>

                        {{-- Bagian 3: Alokasi ke Purchase Order --}}
                        <h5 class="fw-semibold">3. Alokasi ke Purchase Order (Diurutkan dari paling lama)</h5>
                        <p class="text-muted small">Pilih PO yang akan dibayar. Sistem akan melunasi PO dari urutan teratas (paling lama) terlebih dahulu.</p>
                        <div id="po-list-container" class="border rounded p-3" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                            <p class="text-center text-muted" id="po-placeholder">Silakan pilih supplier untuk melihat daftar PO.</p>
                            <table class="table table-sm table-hover d-none" id="po-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="check-all-pos" class="form-check-input"></th>
                                        <th>No. Purchase Order</th>
                                        <th>Jatuh Tempo</th>
                                        <th class="text-end">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody id="po-list-body">
                                    {{-- Daftar PO akan di-load di sini oleh Javascript --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Footer: Total Dipilih dan Tombol Simpan --}}
                        <div class="d-flex justify-content-between align-items-center bg-light p-3 mt-3 rounded">
                            <h5 class="mb-0">Total Tagihan Dipilih: <span id="total-selected-display" class="fw-bold text-danger">Rp 0</span></h5>
                            <button type="submit" class="btn btn-danger btn-lg">Simpan Pembayaran Hutang</button> {{-- Ubah jadi merah --}}
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Menambahkan Skrip Javascript --}}
@push('scripts')
{{-- Include Select2 & AutoNumeric JS --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi Elemen DOM
    const supplierSelect = $('#supplier_id');
    const poTable = document.getElementById('po-table');
    const poListBody = document.getElementById('po-list-body');
    const poPlaceholder = document.getElementById('po-placeholder');
    const checkAll = document.getElementById('check-all-pos');
    const totalSelectedDisplay = document.getElementById('total-selected-display');
    const amountFormattedInput = document.getElementById('total_amount_formatted');
    const amountHiddenInput = document.getElementById('total_amount');
    const debitInfoDiv = document.getElementById('supplier-debit-info');
    const debitBalanceSpan = document.getElementById('supplier-debit-balance');
    const pendingBalanceSpan = document.getElementById('supplier-pending-balance'); // ✅ TAMBAHAN
    const useDebitContainer = document.getElementById('use-debit-container');
    const useDebitCheckbox = document.getElementById('use_debit_balance');
    
    // ✅ PERBAIKAN: Ganti ID selector
    const paymentMethodSelect = document.getElementById('payment_method_id');
    const bankAccountSelect = document.getElementById('company_bank_account_id_batch');
    let currentDebitBalance = 0;
    
    // ✅ PERBAIKAN: Ambil ID default dari PHP
    const defaultPaymentMethodId = "{{ $paymentMethods->first()->payment_method_id ?? '' }}";

    // Inisialisasi AutoNumeric
    const autoNumericInstance = new AutoNumeric(amountFormattedInput, {
        digitGroupSeparator: '.',
        decimalCharacter: ',',
        decimalCharacterAlternative: '.',
        decimalPlaces: 0, // Dibuat 0 agar konsisten dengan batch payment klien
        minimumValue: 0
    });

    // Update hidden input saat nilai berubah
    amountFormattedInput.addEventListener('autoNumeric:rawValueModified', (e) => {
        amountHiddenInput.value = e.detail.newRawValue || 0;
        toggleRequiredFields();
    });

    // Inisialisasi Select2
    supplierSelect.select2({
        theme: 'bootstrap-5',
        placeholder: '-- Cari Supplier --'
    });

    // Fungsi helper format Rupiah
    function formatRupiah(number) {
        if (isNaN(number)) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(number);
    }

    // Fungsi untuk menghitung total sisa tagihan dari PO yang dipilih
    function calculateTotalSelected() {
        let total = 0;
        document.querySelectorAll('.po-checkbox:checked').forEach(checkbox => {
            total += parseFloat(checkbox.dataset.balance || 0);
        });
        totalSelectedDisplay.textContent = formatRupiah(total);
        toggleRequiredFields(); // Panggil toggle saat total berubah
    }

    // Fungsi untuk mengatur required dan state input/select
    function toggleRequiredFields() {
        const selectedPOBalanceString = totalSelectedDisplay.textContent || 'Rp 0';
        const selectedPOBalance = parseFloat(selectedPOBalanceString.replace(/[^0-9,-]+/g,"").replace(",", ".")) || 0;

        const useDebitIsChecked = useDebitCheckbox.checked;
        const inputAmountValue = parseFloat(amountHiddenInput.value || 0);
        const debitIsSufficient = currentDebitBalance >= selectedPOBalance && selectedPOBalance > 0;

        if (useDebitIsChecked) {
            if (debitIsSufficient) {
                // Deposit cukup: dana input tidak perlu & nonaktif
                if (!amountFormattedInput.disabled) {
                     autoNumericInstance.set(0); // Set ke 0
                }
                amountFormattedInput.required = false;
                amountFormattedInput.disabled = true;
                paymentMethodSelect.required = false;
                paymentMethodSelect.disabled = true;
                paymentMethodSelect.value = "";

                bankAccountSelect.required = false; // ✅ 2. TAMBAHKAN INI
                bankAccountSelect.disabled = true; // ✅ 3. TAMBAHKAN INI
                bankAccountSelect.value = ""; // ✅ 4. TAMBAHKAN INI
            } else {
                // Deposit kurang: dana input perlu & aktif
                amountFormattedInput.required = true;
                amountFormattedInput.disabled = false;
                paymentMethodSelect.required = true;
                paymentMethodSelect.disabled = false;
                // ✅ PERBAIKAN: Gunakan ID
                if (!paymentMethodSelect.value) paymentMethodSelect.value = defaultPaymentMethodId;
                bankAccountSelect.required = true; // ✅ 5. TAMBAHKAN INI
                bankAccountSelect.disabled = false; // ✅ 6. TAMBAHKAN INI
            }
        } else {
            // Tidak pakai deposit: dana input perlu & aktif
            amountFormattedInput.required = true;
            amountFormattedInput.disabled = false;
            // Metode wajib jika ada dana input > 0
            paymentMethodSelect.required = inputAmountValue > 0;
            paymentMethodSelect.disabled = false;
             // ✅ PERBAIKAN: Gunakan ID
            if (paymentMethodSelect.required && !paymentMethodSelect.value) {
                paymentMethodSelect.value = defaultPaymentMethodId;
            } 
            bankAccountSelect.required = inputAmountValue > 0;
            bankAccountSelect.disabled = false;
            
            else if (!paymentMethodSelect.required) {
                 paymentMethodSelect.value = "";
            }
        }
    }

    // Event listener untuk checkbox 'Use Deposit'
     useDebitCheckbox.addEventListener('change', toggleRequiredFields);

    // Event listener untuk checkbox 'Check All'
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.po-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        calculateTotalSelected(); // Hitung ulang total & panggil toggleRequired
    });

    // Fungsi untuk menambahkan event listener ke checkbox PO individual
    function addPOCheckboxListeners() {
        document.querySelectorAll('.po-checkbox').forEach(cb => {
            cb.removeEventListener('change', handlePOCheckboxChange); // Cegah duplikat
            cb.addEventListener('change', handlePOCheckboxChange);
        });
    }

    // Handler untuk perubahan checkbox PO individual
    function handlePOCheckboxChange() {
        calculateTotalSelected(); // Panggil fungsi utama
    }

    // Event listener utama saat supplier dipilih
    supplierSelect.on('change', async function () {
        const supplierId = this.value;

        // Reset tampilan
        debitInfoDiv.classList.add('d-none');
        useDebitContainer.style.display = 'none';
        useDebitCheckbox.checked = false;
        currentDebitBalance = 0;
        poPlaceholder.textContent = 'Memuat data...';
        poTable.classList.add('d-none');
        poListBody.innerHTML = '';
        checkAll.checked = false;
        autoNumericInstance.set(0); // Reset input amount jadi 0

        if (!supplierId) {
            poPlaceholder.textContent = 'Silakan pilih supplier untuk melihat daftar PO.';
            calculateTotalSelected(); // Reset total jadi 0
            toggleRequiredFields(); // Reset field required
            return;
        }

        try {
            // 1. Fetch data Supplier (termasuk saldo deposit)
            const supplierResponse = await fetch(`/api/suppliers/${supplierId}/details`);
            if (!supplierResponse.ok) throw new Error('Gagal ambil data supplier');
            const supplierData = await supplierResponse.json();
            
            // ✅ PERBAIKAN: Baca 'balance' dan 'pending_balance' dari API
            currentDebitBalance = parseFloat(supplierData.balance) || 0;
            const pendingBalance = parseFloat(supplierData.pending_balance) || 0;

            debitBalanceSpan.textContent = formatRupiah(currentDebitBalance);
            pendingBalanceSpan.textContent = formatRupiah(pendingBalance); // ✅ Tampilkan
            debitInfoDiv.classList.remove('d-none'); // Selalu tampilkan

            if (currentDebitBalance > 0) {
                useDebitContainer.style.display = 'block';
            }

            // 2. Fetch data PO yang belum lunas
            const poResponse = await fetch(`/api/suppliers/${supplierId}/unpaid-purchase-orders`);
            if (!poResponse.ok) throw new Error('Gagal mengambil data PO');
            const pos = await poResponse.json();

            // Tampilkan daftar PO atau pesan jika kosong
            if (pos.length === 0) {
                poPlaceholder.textContent = 'Supplier ini tidak memiliki tagihan PO.';
            } else {
                pos.forEach(po => {
                    // Buat baris tabel untuk setiap PO
                    const row = `
                        <tr>
                            <td>
                                <input class="form-check-input po-checkbox"
                                       type="checkbox"
                                       name="po_ids[]"
                                       value="${po.po_id}"
                                       data-balance="${po.sisa_tagihan}">
                            </td>
                            <td>${po.po_number}</td>
                            <td>${po.due_date_formatted}</td>
                            <td class="text-end">${formatRupiah(po.sisa_tagihan)}</td>
                        </tr>
                    `;
                    poListBody.insertAdjacentHTML('beforeend', row);
                });
                poPlaceholder.textContent = '';
                poTable.classList.remove('d-none');
                addPOCheckboxListeners(); // Tambahkan listener ke checkbox baru
            }

        } catch (error) {
            poPlaceholder.textContent = 'Gagal memuat data. Silakan coba lagi.';
            console.error('Error fetching data:', error);
            debitInfoDiv.classList.add('d-none');
            useDebitContainer.style.display = 'none';
        }

        calculateTotalSelected(); // Hitung total (akan 0 jika tidak ada PO)
        toggleRequiredFields(); // Atur state field di akhir
    });

    // Panggil sekali di awal untuk set state required awal
    toggleRequiredFields();
});
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Buat Penyesuaian PO Manual untuk PO: {{ $purchaseOrder->po_number }}</h4>
                </div>
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info mb-4">
                        PO ini memiliki sisa utang: <strong>Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}</strong>
                    </div>

                    <form action="{{ route('purchase-order-adjustments.store.manual') }}" method="POST">
                        @csrf
                        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->po_id }}">

                        <div class="row g-3">
                            <!-- Tanggal Penyesuaian -->
                            <div class="col-md-6">
                                <label for="adjustment_date" class="form-label fw-semibold">Tanggal Penyesuaian</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="adjustment_date" 
                                       name="adjustment_date" 
                                       value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" 
                                       required>
                            </div>

                            <!-- Tipe Penyesuaian -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipe Penyesuaian</label>
                                <div class="border rounded p-3 bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="type" 
                                               id="type_credit_note" 
                                               value="credit_note" 
                                               {{ old('type', 'credit_note') == 'credit_note' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="type_credit_note">
                                            <strong>Nota Kredit (Credit Note)</strong>
                                        </label>
                                        <div class="form-text mt-0">
                                            Supplier memberi kita diskon/potongan. Ini akan **mengurangi** sisa utang kita.
                                        </div>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="type" 
                                               id="type_debit_note" 
                                               value="debit_note" 
                                               {{ old('type') == 'debit_note' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="type_debit_note">
                                            <strong>Nota Debit (Debit Note)</strong>
                                        </label>
                                        <div class="form-text mt-0">
                                            Supplier menagih kita biaya tambahan. Ini akan **menambah** sisa utang kita.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Nilai Penyesuaian -->
                            <div class="col-12">
                                <label for="amount-formatted" class="form-label fw-semibold">Nilai Penyesuaian (Rp)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="amount-formatted" 
                                       value="{{ old('amount') }}" 
                                       required>
                                <input type="hidden" 
                                       name="amount" 
                                       id="amount-hidden" 
                                       value="{{ old('amount') }}">
                            </div>
                            
                            <!-- Alasan Penyesuaian -->
                            <div class="col-12">
                                <label for="reason" class="form-label fw-semibold">Alasan Penyesuaian (Wajib Diisi)</label>
                                <textarea class="form-control" 
                                          name="reason" 
                                          id="reason" 
                                          rows="3" 
                                          placeholder="Contoh: Koreksi biaya kirim tambahan dari supplier." 
                                          required>{{ old('reason') }}</textarea>
                            </div>
                        </div>

                        <!-- Opsi Penanganan Kelebihan Bayar -->
                        <div class="row mt-4" id="overpayment-section" style="display: none;">
                            <div class="col-12">
                                <div class="card border-info shadow-sm">
                                    <div class="card-header bg-info text-white fw-semibold">
                                        Opsi Penanganan Kelebihan Bayar
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text small text-muted">
                                            Jika penyesuaian ini (terutama Nota Kredit) menyebabkan kelebihan bayar pada invoice/PO yang sudah lunas, tentukan apa yang harus sistem lakukan:
                                        </p>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="overpayment_action" 
                                                   id="overpayment_deposit" 
                                                   value="deposit" 
                                                   checked>
                                            <label class="form-check-label" for="overpayment_deposit">
                                                <strong>Simpan sebagai Deposit (Default)</strong><br>
                                                <small>Kelebihan bayar akan otomatis masuk ke saldo Deposit Klien/Supplier.</small>
                                            </label>
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="overpayment_action" 
                                                   id="overpayment_refund" 
                                                   value="refund">
                                            <label class="form-check-label" for="overpayment_refund">
                                                <strong>Proses Pengembalian Dana (Manual Refund)</strong><br>
                                                <small>Saldo akan dibiarkan negatif (minus). Anda harus memproses pengembalian dana ini secara manual (misal: transfer balik).</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('purchase-order-adjustments.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}" 
                               class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Penyesuaian Manual</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.10.1/dist/autonumeric.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi AutoNumeric untuk format currency
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');
    const overpaymentSection = document.getElementById('overpayment-section');
    const creditNoteRadio = document.getElementById('type_credit_note');
    const debitNoteRadio = document.getElementById('type_debit_note');

    const autoNumericInstance = new AutoNumeric(amountFormatted, {
        decimalPlaces: 0,
        digitGroupSeparator: '.',
        decimalCharacter: ',',
        minimumValue: '0'
    });

    // Update hidden field ketika nilai berubah
    amountFormatted.addEventListener('autoNumeric:rawValueModified', function(event) {
        amountHidden.value = event.detail.newRawValue;
    });

    // Set nilai awal jika ada data old()
    if (amountHidden.value) {
        autoNumericInstance.set(amountHidden.value);
    }

    // Fungsi untuk menampilkan/menyembunyikan section overpayment
    function toggleOverpaymentSection() {
        if (creditNoteRadio.checked) {
            overpaymentSection.style.display = 'block';
        } else {
            overpaymentSection.style.display = 'none';
        }
    }

    // Event listener untuk perubahan tipe penyesuaian
    creditNoteRadio.addEventListener('change', toggleOverpaymentSection);
    debitNoteRadio.addEventListener('change', toggleOverpaymentSection);

    // Jalankan sekali saat halaman dimuat untuk set status awal
    toggleOverpaymentSection();

    // Validasi form sebelum submit
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        const amountValue = parseFloat(amountHidden.value) || 0;
        const adjustmentType = document.querySelector('input[name="type"]:checked')?.value;
        const reason = document.getElementById('reason').value.trim();
        
        if (amountValue <= 0) {
            event.preventDefault();
            alert('Nilai penyesuaian harus lebih dari 0.');
            amountFormatted.focus();
            return;
        }
        
        if (!adjustmentType) {
            event.preventDefault();
            alert('Silakan pilih tipe penyesuaian.');
            return;
        }
        
        if (!reason) {
            event.preventDefault();
            alert('Silakan isi alasan penyesuaian.');
            document.getElementById('reason').focus();
            return;
        }
    });
});
</script>
@endpush
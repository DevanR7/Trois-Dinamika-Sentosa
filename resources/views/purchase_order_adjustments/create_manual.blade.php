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
                            {{-- Tanggal Penyesuaian --}}
                            <div class="col-md-6">
                                <label for="adjustment_date" class="form-label fw-semibold">Tanggal Penyesuaian</label>
                                <input type="date" class="form-control" id="adjustment_date" name="adjustment_date" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                            </div>

                            {{-- Tipe Penyesuaian --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipe Penyesuaian</label>
                                <div class="border rounded p-3 bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="type_credit_note" value="credit_note" {{ old('type', 'credit_note') == 'credit_note' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="type_credit_note">
                                            <strong>Nota Kredit (Credit Note)</strong>
                                        </label>
                                        <div class="form-text mt-0">
                                            Supplier memberi kita diskon/potongan. Ini akan **mengurangi** sisa utang kita.
                                        </div>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="type" id="type_debit_note" value="debit_note" {{ old('type') == 'debit_note' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="type_debit_note">
                                            <strong>Nota Debit (Debit Note)</strong>
                                        </label>
                                        <div class="form-text mt-0">
                                            Supplier menagih kita biaya tambahan. Ini akan **menambah** sisa utang kita.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Nilai Penyesuaian --}}
                            <div class="col-12">
                                <label for="amount-formatted" class="form-label fw-semibold">Nilai Penyesuaian (Rp)</label>
                                <input type="text" class="form-control" id="amount-formatted" value="{{ old('amount') }}" required>
                                <input type="hidden" name="amount" id="amount-hidden" value="{{ old('amount') }}">
                            </div>
                            
                            {{-- Alasan Penyesuaian --}}
                            <div class="col-12">
                                <label for="reason" class="form-label fw-semibold">Alasan Penyesuaian (Wajib Diisi)</label>
                                <textarea class="form-control" name="reason" id="reason" rows="3" placeholder="Contoh: Koreksi biaya kirim tambahan dari supplier." required>{{ old('reason') }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('purchase-order-adjustments.create', ['purchase_order_id' => $purchaseOrder->po_id]) }}" class="btn btn-secondary me-2">Batal</a>
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
{{-- Asumsi Anda sudah memuat Select2 dan AutoNumeric di layout utama --}}
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.10.1/dist/autonumeric.min.js"></script> {{-- Pastikan AutoNumeric dimuat --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Select2 - TIDAK PERLU DI SINI KARENA SUDAH ADA PO ID
    // $('#purchase_order_id').select2({
    //     theme: 'bootstrap-5',
    //     placeholder: '-- Cari dan Pilih Nomor PO --'
    // });

    // 2. Inisialisasi AutoNumeric
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');

    const autoNumericInstance = new AutoNumeric(amountFormatted, {
        decimalPlaces: 0,
        digitGroupSeparator: '.',
        decimalCharacter: ',',
        minimumValue: '0'
    });

    // 3. Update hidden field
    amountFormatted.addEventListener('autoNumeric:rawValueModified', function(event) {
        amountHidden.value = event.detail.newRawValue;
    });

    // 4. Set nilai awal jika ada old()
    if (amountHidden.value) {
        autoNumericInstance.set(amountHidden.value);
    }
});
</script>
@endpush
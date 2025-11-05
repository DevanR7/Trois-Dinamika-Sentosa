@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">Penyesuaian Manual untuk Invoice #{{ $invoice->invoice_number }}</h4>
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

                    {{-- Form ini men-submit ke 'store.manual' --}}
                    <form action="{{ route('invoice-adjustments.store.manual') }}" method="POST">
                        @csrf
                        <input type="hidden" name="sales_invoice_id" value="{{ $invoice->invoice_id }}">
                        
                        <div class="alert alert-info">
                            Sisa Tagihan Saat Ini: <strong>Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</strong>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="adjustment_date" class="form-label fw-semibold">Tanggal Penyesuaian</label>
                                <input type="date" class="form-control" id="adjustment_date" name="adjustment_date" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="type" class="form-label fw-semibold">Tipe Penyesuaian</label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="credit_note" @selected(old('type') == 'credit_note')>Nota Kredit (Mengurangi Tagihan)</option>
                                    <option value="debit_note" @selected(old('type') == 'debit_note')>Nota Debit (Menambah Tagihan)</option>
                                </select>
                            </div>

                            <div class="col-12">
                                {{-- Kita gunakan AutoNumeric dari file lama Anda --}}
                                <label for="amount-formatted" class="form-label fw-semibold">Nilai Penyesuaian (Rp)</label>
                                <input type="text" class="form-control" id="amount-formatted" value="{{ old('amount') }}" required>
                                <input type="hidden" name="amount" id="amount-hidden" value="{{ old('amount') }}">
                            </div>
                            
                            <div class="col-12">
                                <label for="reason" class="form-label fw-semibold">Alasan Penyesuaian (Wajib Diisi)</label>
                                <textarea class="form-control" name="reason" id="reason" rows="3" placeholder="Contoh: Goodwill diskon tambahan" required>{{ old('reason') }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('invoice-adjustments.create', $invoice->invoice_id) }}" class="btn btn-light me-2">Kembali ke Pilihan</a>
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
{{-- Script dari file create.blade.php Anda yang lama --}}
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi AutoNumeric
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');

    const autoNumericInstance = new AutoNumeric(amountFormatted, {
        decimalPlaces: 0,
        digitGroupSeparator: '.',
        decimalCharacter: ',',
        minimumValue: '0'
    });

    // 2. Update hidden field saat nilai diformat
    amountFormatted.addEventListener('autoNumeric:rawValueModified', function(event) {
        amountHidden.value = event.detail.newRawValue;
    });

    // 3. Set nilai awal jika ada old()
    if (amountHidden.value) {
        autoNumericInstance.set(amountHidden.value);
    }
});
</script>
@endpush
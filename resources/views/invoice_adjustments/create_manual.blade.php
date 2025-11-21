@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Koreksi Manual Invoice</h3>
            <p class="text-muted mb-0 small">
                Input nominal langsung untuk Invoice: 
                <span class="text-primary fw-bold">{{ $invoice->invoice_number }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('invoice-adjustments.create') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            {{-- INFO SISA TAGIHAN --}}
            <div class="alert alert-light border shadow-sm d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet2 fs-4 me-3 text-primary"></i>
                    <div>
                        <small class="text-muted d-block">Sisa Tagihan Saat Ini</small>
                        <strong class="text-dark">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</strong>
                    </div>
                </div>
                <span class="badge bg-info text-dark bg-opacity-25 border border-info">Informasi</span>
            </div>

            <form action="{{ route('invoice-adjustments.store.manual') }}" method="POST" id="manual-adjustment-form">
                @csrf
                <input type="hidden" name="sales_invoice_id" value="{{ $invoice->invoice_id }}">

                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-input-cursor-text"></i> Form Input Manual</div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row g-4">
                            
                            {{-- 1. TANGGAL --}}
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">TANGGAL PENYESUAIAN</label>
                                <input type="date" class="form-control" name="adjustment_date" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                            </div>

                            {{-- 2. TIPE PENYESUAIAN (VISUAL RADIO) --}}
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted mb-2">JENIS KOREKSI</label>
                                <div class="row g-3">
                                    {{-- Opsi Nota Kredit --}}
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="type" id="type_credit_note" value="credit_note" checked>
                                        <label class="btn btn-outline-success w-100 p-3 text-start d-flex align-items-center justify-content-between shadow-sm" for="type_credit_note">
                                            <div>
                                                <span class="d-block fw-bold"><i class="bi bi-arrow-down-circle me-1"></i> Nota Kredit</span>
                                                <small class="text-muted" style="font-size: 0.75rem;">Potongan/Diskon (Kurangi Tagihan)</small>
                                            </div>
                                            <i class="bi bi-check-circle-fill fs-4"></i>
                                        </label>
                                    </div>
                                    {{-- Opsi Nota Debit --}}
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="type" id="type_debit_note" value="debit_note">
                                        <label class="btn btn-outline-danger w-100 p-3 text-start d-flex align-items-center justify-content-between shadow-sm" for="type_debit_note">
                                            <div>
                                                <span class="d-block fw-bold"><i class="bi bi-arrow-up-circle me-1"></i> Nota Debit</span>
                                                <small class="text-muted" style="font-size: 0.75rem;">Biaya Tambahan (Tambah Tagihan)</small>
                                            </div>
                                            <i class="bi bi-check-circle-fill fs-4"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. NOMINAL --}}
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">NILAI PENYESUAIAN (RP)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-muted">Rp</span>
                                    <input type="text" class="form-control fw-bold text-dark" id="amount-formatted" placeholder="0" required>
                                </div>
                                <input type="hidden" name="amount" id="amount-hidden">
                            </div>

                            {{-- 4. ALASAN --}}
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">ALASAN (WAJIB)</label>
                                <textarea class="form-control" name="reason" id="reason" rows="3" placeholder="Contoh: Diskon loyalitas, koreksi harga..." required></textarea>
                            </div>
                        </div>

                        {{-- OPSI KELEBIHAN BAYAR (VISUAL CARD RADIO) --}}
                        <div class="mt-4" id="overpayment-section" style="display: none;">
                            <div class="card border-0 shadow-sm" style="background-color: #e0f2fe;"> {{-- Background Biru Muda --}}
                                <div class="card-body p-4">
                                    
                                    <div class="d-flex align-items-start mb-3">
                                        <i class="bi bi-info-circle-fill text-primary fs-5 me-2 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold text-primary mb-1">Penanganan Kelebihan Bayar</h6>
                                            <p class="card-text small text-muted">
                                                Jika Nota Kredit ini menyebabkan invoice lunas menjadi minus (lebih bayar), apa yang harus dilakukan?
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-column gap-2">
                                        {{-- Opsi 1: Deposit --}}
                                        <label class="card p-3 border border-primary border-opacity-25 shadow-sm cursor-pointer position-relative bg-white" for="op_deposit">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="overpayment_action" id="op_deposit" value="deposit" checked style="transform: scale(1.2);">
                                                </div>
                                                <div class="ms-3">
                                                    <span class="d-block fw-bold text-dark">Simpan ke Saldo Kredit (Default)</span>
                                                    <small class="text-muted">Otomatis menambah saldo deposit klien. Bisa dipakai untuk invoice lain.</small>
                                                </div>
                                            </div>
                                        </label>
                        
                                        {{-- Opsi 2: Refund --}}
                                        <label class="card p-3 border border-secondary border-opacity-25 shadow-sm cursor-pointer position-relative bg-white" for="op_refund">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="overpayment_action" id="op_refund" value="refund" style="transform: scale(1.2);">
                                                </div>
                                                <div class="ms-3">
                                                    <span class="d-block fw-bold text-dark">Biarkan Minus (Refund Manual)</span>
                                                    <small class="text-muted">Saldo tagihan akan negatif. Anda perlu mencatat pengembalian uang secara manual.</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('invoice-adjustments.create') }}" class="btn btn-light border">Batal</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Simpan
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/autonumeric@4.10.1/dist/autonumeric.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');
    const creditNoteRadio = document.getElementById('type_credit_note');
    const debitNoteRadio = document.getElementById('type_debit_note');
    const overpaymentSection = document.getElementById('overpayment-section');

    // Init AutoNumeric
    if(amountFormatted) {
        new AutoNumeric(amountFormatted, { decimalPlaces: 0, digitGroupSeparator: '.', decimalCharacter: ',', minimumValue: '0' });
        amountFormatted.addEventListener('autoNumeric:rawValueModified', e => amountHidden.value = e.detail.newRawValue);
    }

    // Toggle Section
    function toggleOverpayment() {
        if(creditNoteRadio.checked) {
            overpaymentSection.style.display = 'block';
        } else {
            overpaymentSection.style.display = 'none';
        }
    }
    
    creditNoteRadio.addEventListener('change', toggleOverpayment);
    debitNoteRadio.addEventListener('change', toggleOverpayment);
    
    // Init State
    toggleOverpayment();
});
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    {{-- HEADER HALAMAN --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Koreksi Manual PO</h3>
            <p class="text-muted mb-0 small">
                Input nominal langsung untuk PO: 
                <span class="text-primary fw-bold">{{ $purchaseOrder->po_number }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('purchase-order-adjustments.create') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ERROR HANDLING --}}
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            {{-- INFO SISA UTANG --}}
            <div class="alert alert-light border shadow-sm d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-wallet2 fs-4 me-3 text-primary"></i>
                    <div>
                        <small class="text-muted d-block">Sisa Tagihan Saat Ini</small>
                        <strong class="text-dark">Rp {{ number_format($purchaseOrder->remaining_balance, 0, ',', '.') }}</strong>
                    </div>
                </div>
                <span class="badge bg-info text-dark bg-opacity-25 border border-info">Informasi</span>
            </div>

            <form action="{{ route('purchase-order-adjustments.store.manual') }}" method="POST" id="manual-adjustment-form">
                @csrf
                <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->po_id }}">

                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-input-cursor-text"></i> Form Input Manual</div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row g-4">
                            
                            {{-- 1. TANGGAL --}}
                            <div class="col-md-12">
                                <label for="adjustment_date" class="form-label fw-semibold small text-muted">TANGGAL PENYESUAIAN</label>
                                <input type="date" class="form-control" id="adjustment_date" name="adjustment_date" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}" required>
                            </div>

                            {{-- 2. TIPE PENYESUAIAN (VISUAL RADIO) --}}
                            <div class="col-md-12">
                                <label class="form-label fw-semibold small text-muted mb-2">JENIS KOREKSI</label>
                                <div class="row g-3">
                                    {{-- Opsi Nota Kredit --}}
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="type" id="type_credit_note" value="credit_note" {{ old('type', 'credit_note') == 'credit_note' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success w-100 p-3 text-start d-flex align-items-center justify-content-between" for="type_credit_note">
                                            <div>
                                                <span class="d-block fw-bold"><i class="bi bi-arrow-down-circle me-1"></i> Nota Kredit</span>
                                                <small style="font-size: 0.75rem;">Potongan/Diskon (Kurangi Utang)</small>
                                            </div>
                                            <i class="bi bi-check-circle-fill fs-5"></i>
                                        </label>
                                    </div>
                                    {{-- Opsi Nota Debit --}}
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="type" id="type_debit_note" value="debit_note" {{ old('type') == 'debit_note' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger w-100 p-3 text-start d-flex align-items-center justify-content-between" for="type_debit_note">
                                            <div>
                                                <span class="d-block fw-bold"><i class="bi bi-arrow-up-circle me-1"></i> Nota Debit</span>
                                                <small style="font-size: 0.75rem;">Tagihan Tambahan (Tambah Utang)</small>
                                            </div>
                                            <i class="bi bi-check-circle-fill fs-5"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. NOMINAL --}}
                            <div class="col-md-12">
                                <label for="amount-formatted" class="form-label fw-semibold small text-muted">NILAI PENYESUAIAN (RP)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="text" class="form-control fw-bold" id="amount-formatted" placeholder="0" required>
                                </div>
                                <input type="hidden" name="amount" id="amount-hidden" value="{{ old('amount') }}">
                            </div>

                            {{-- 4. ALASAN --}}
                            <div class="col-md-12">
                                <label for="reason" class="form-label fw-semibold small text-muted">ALASAN (WAJIB)</label>
                                <textarea class="form-control" name="reason" id="reason" rows="3" placeholder="Contoh: Kesalahan input harga manual, atau diskon khusus dari supplier." required>{{ old('reason') }}</textarea>
                            </div>

                        </div>

                        {{-- OPSI KELEBIHAN BAYAR (HANYA MUNCUL JIKA NOTA KREDIT) --}}
                        <div class="mt-4" id="overpayment-section" style="display: none;">
    <div class="card border-0 shadow-sm" style="background-color: #e0f2fe;"> {{-- Background Biru Muda --}}
        <div class="card-body p-4">
            
            {{-- Header Section --}}
            <div class="d-flex align-items-start mb-3">
                <i class="bi bi-info-circle-fill text-primary fs-5 me-2 mt-1"></i>
                <div>
                    <h6 class="fw-bold text-primary mb-1">Penanganan Kelebihan Bayar</h6>
                    <p class="card-text small text-muted">
                        Jika Nota Kredit ini menyebabkan invoice lunas menjadi minus (lebih bayar), apa yang harus dilakukan?
                    </p>
                </div>
            </div>
            
            {{-- Pilihan (Radio Cards) --}}
            <div class="d-flex flex-column gap-2">
                
                {{-- Opsi 1: Deposit --}}
                <label class="card p-3 border border-primary border-opacity-25 shadow-sm cursor-pointer position-relative bg-white" for="overpayment_deposit">
                    <div class="d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="overpayment_action" id="overpayment_deposit" value="deposit" checked style="transform: scale(1.2);">
                        </div>
                        <div class="ms-3">
                            <span class="d-block fw-bold text-dark">Simpan ke Deposit (Default)</span>
                            <small class="text-muted">Otomatis menambah saldo deposit supplier. Bisa digunakan untuk memotong tagihan berikutnya.</small>
                        </div>
                    </div>
                </label>

                {{-- Opsi 2: Refund --}}
                <label class="card p-3 border border-secondary border-opacity-25 shadow-sm cursor-pointer position-relative bg-white" for="overpayment_refund">
                    <div class="d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="overpayment_action" id="overpayment_refund" value="refund" style="transform: scale(1.2);">
                        </div>
                        <div class="ms-3">
                            <span class="d-block fw-bold text-dark">Biarkan Minus (Refund Manual)</span>
                            <small class="text-muted">Saldo tagihan akan menjadi negatif. Anda perlu mencatat pengembalian uang (Refund) secara manual nanti.</small>
                        </div>
                    </div>
                </label>

            </div>
        </div>
    </div>
</div>

                        {{-- ACTIONS --}}
                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('purchase-order-adjustments.create') }}?purchase_order_id={{ $purchaseOrder->po_id }}" class="btn btn-light border">Batal</a>
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
    // 1. AutoNumeric
    const amountFormatted = document.getElementById('amount-formatted');
    const amountHidden = document.getElementById('amount-hidden');
    
    if(amountFormatted) {
        const autoNumericInstance = new AutoNumeric(amountFormatted, {
            decimalPlaces: 0,
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            minimumValue: '0',
            modifyValueOnWheel: false
        });

        // Update hidden field
        amountFormatted.addEventListener('autoNumeric:rawValueModified', function(event) {
            amountHidden.value = event.detail.newRawValue;
        });

        // Set old value
        if (amountHidden.value) {
            autoNumericInstance.set(amountHidden.value);
        }
    }

    // 2. Toggle Overpayment Section
    const overpaymentSection = document.getElementById('overpayment-section');
    const creditNoteRadio = document.getElementById('type_credit_note');
    const debitNoteRadio = document.getElementById('type_debit_note');

    function toggleOverpaymentSection() {
        if (creditNoteRadio && creditNoteRadio.checked) {
            // Efek slide down sederhana (jQuery tidak wajib, pakai CSS display saja cukup aman)
            overpaymentSection.style.display = 'block';
        } else {
            overpaymentSection.style.display = 'none';
        }
    }

    if(creditNoteRadio && debitNoteRadio) {
        creditNoteRadio.addEventListener('change', toggleOverpaymentSection);
        debitNoteRadio.addEventListener('change', toggleOverpaymentSection);
        
        // Init state
        toggleOverpaymentSection();
    }

    // 3. Validation
    const form = document.getElementById('manual-adjustment-form');
    if(form) {
        form.addEventListener('submit', function(event) {
            const amountValue = parseFloat(amountHidden.value) || 0;
            const reason = document.getElementById('reason').value.trim();
            
            if (amountValue <= 0) {
                event.preventDefault();
                Swal.fire('Error', 'Nilai penyesuaian harus lebih dari 0.', 'error');
                return;
            }
            
            if (!reason) {
                event.preventDefault();
                Swal.fire('Error', 'Silakan isi alasan penyesuaian.', 'warning');
                return;
            }
        });
    }
});
</script>
@endpush
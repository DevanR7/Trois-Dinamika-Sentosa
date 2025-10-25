@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Catat Pembayaran Cicilan</h2>
                    <p class="mb-0 text-muted">Untuk pinjaman: <strong>{{ $loan->lender_name }}</strong></p>
                </div>
                <div class="card-body">
                    {{-- Alert untuk sisa pokok --}}
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle-fill"></i> Sisa Pokok Pinjaman Saat Ini: 
                        <strong class="fs-5">Rp {{ number_format($loan->remaining_balance, 0, ',', '.') }}</strong>
                    </div>

                    <form action="{{ route('loans.payments.store', $loan) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="principal_paid" class="form-label">Jumlah Bayar Pokok (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('principal_paid') is-invalid @enderror" id="principal_paid" name="principal_paid" value="{{ old('principal_paid', 0) }}" required>
                                <div class="form-text">Bagian ini akan mengurangi sisa utang Anda.</div>
                                @error('principal_paid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interest_paid" class="form-label">Jumlah Bayar Bunga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('interest_paid') is-invalid @enderror" id="interest_paid" name="interest_paid" value="{{ old('interest_paid', 0) }}" required>
                                <div class="form-text">Bagian ini akan dicatat sebagai "Beban Bunga".</div>
                                @error('interest_paid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                             <div class="col-12 mb-3">
                                <label for="notes" class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2" placeholder="Contoh: Pembayaran cicilan ke-1">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h2 class="fw-bold mb-0">Catat Pinjaman Baru</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('loans.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                             <div class="col-12 mb-3">
                                <label for="lender_name" class="form-label">Nama Pemberi Pinjaman <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lender_name') is-invalid @enderror" id="lender_name" name="lender_name" value="{{ old('lender_name') }}" placeholder="Contoh: Bank BCA" required>
                                @error('lender_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="loan_date" class="form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('loan_date') is-invalid @enderror" id="loan_date" name="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" required>
                                @error('loan_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="principal_amount" class="form-label">Jumlah Pokok Pinjaman (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('principal_amount') is-invalid @enderror" id="principal_amount" name="principal_amount" value="{{ old('principal_amount') }}" placeholder="Contoh: 100000000" required>
                                @error('principal_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Catatan opsional (misal: Pinjaman KPR, Tenor 5 thn)">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-dark">
                                <i class="bi bi-save-fill"></i> Simpan Pinjaman
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
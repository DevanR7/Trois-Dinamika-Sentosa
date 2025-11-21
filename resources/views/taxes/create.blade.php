@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Tambah Tarif Pajak Baru</h3>
            <p class="text-muted small mb-0">Definisikan tarif PPN, PPh, atau pajak lainnya.</p>
        </div>
        <div>
            <a href="{{ route('taxes.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card card-transaction border-0 shadow-sm">
                <div class="card-header bg-white p-4 border-bottom">
                    <div class="form-section-title mb-0"><i class="bi bi-percent"></i> Form Data Tarif</div>
                </div>
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                            <ul class="mb-0 small ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('taxes.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold small text-muted">NAMA PAJAK</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: PPN, PPh 23" required>
                        </div>
                        <div class="mb-3">
                            <label for="rate" class="form-label fw-bold small text-muted">TARIF (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control text-end fw-bold" id="rate" name="rate" value="{{ old('rate') }}" placeholder="11.00" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Gunakan titik desimal jika diperlukan (misal: 11.50).</div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input cursor-pointer" id="is_active" name="is_active" value="1" checked />
                            <label class="form-check-label" for="is_active">
                                Jadikan tarif ini aktif?
                            </label>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('taxes.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Tarif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
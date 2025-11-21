@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Tambah Metode Pembayaran</h3>
            <p class="text-muted mb-0 small">Buat opsi pembayaran baru untuk transaksi.</p>
        </div>
        <div>
            <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form action="{{ route('payment-methods.store') }}" method="POST">
                @csrf

                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-credit-card"></i> Form Data Metode</div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold small text-muted">NAMA METODE <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Transfer BCA, Giro Mundur" required>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label fw-bold small text-muted">TIPE PROSES <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="direct" @selected(old('type') == 'direct')>Direct (Langsung Masuk Kas/Bank)</option>
                                <option value="pending" @selected(old('type') == 'pending')>Pending (Butuh Kliring - Cek/Giro)</option>
                                <option value="gateway" @selected(old('type') == 'gateway')>Payment Gateway (Otomatis)</option>
                            </select>
                            <div class="form-text text-muted small">Pilih "Pending" jika pembayaran butuh proses pencairan (Cek/Giro).</div>
                        </div>

                        <div class="mb-3">
                            <label for="required_fields_config" class="form-label fw-bold small text-muted">DATA WAJIB ISI (PELANGGAN) <span class="text-danger">*</span></label>
                            <select class="form-select" id="required_fields_config" name="required_fields_config" required>
                                <option value="none" @selected(old('required_fields_config', 'none') == 'none')>Tidak Ada (Langsung Nominal)</option>
                                <option value="proof_only" @selected(old('required_fields_config') == 'proof_only')>Wajib Upload Bukti Foto</option>
                                <option value="reference_only" @selected(old('required_fields_config') == 'reference_only')>Wajib Isi No. Referensi</option>
                                <option value="proof_and_reference" @selected(old('required_fields_config') == 'proof_and_reference')>Wajib Bukti & Referensi</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">STATUS</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Aktifkan metode ini</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end pt-3 border-top">
                            <a href="{{ route('payment-methods.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
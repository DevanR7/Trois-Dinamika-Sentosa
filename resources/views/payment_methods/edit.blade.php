@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Edit Metode Pembayaran</h4>
                </div>
                <div class="card-body p-4">
                    {{-- Di sini kita gunakan $method, bukan $account --}}
                    <form action="{{ route('payment-methods.update', $paymentMethod->payment_method_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Metode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" 
                                   value="{{ old('name', $paymentMethod->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Tipe Metode <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="direct" @selected(old('type', $paymentMethod->type) == 'direct')>Direct (Cash, Transfer Langsung)</option>
                                <option value="pending" @selected(old('type', $paymentMethod->type) == 'pending')>Pending (Giro, Cek)</option>
                                <option value="gateway" @selected(old('type', $paymentMethod->type) == 'gateway')>Payment Gateway (Midtrans, dll)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- =============================================== --}}
                        {{-- ✅ TAMBAHKAN DROPDOWN BARU INI --}}
                        {{-- =============================================== --}}
                        <div class="mb-3">
                            <label for="required_fields_config" class="form-label">Konfigurasi Form <span class="text-danger">*</span></label>
                            <select class="form-select @error('required_fields_config') is-invalid @enderror" id="required_fields_config" name="required_fields_config" required>
                                <option value="none" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'none')>
                                    Tidak Perlu Input Tambahan (Contoh: Cash)
                                </option>
                                <option value="proof_only" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'proof_only')>
                                    Hanya Bukti Foto (Contoh: Transfer)
                                </option>
                                <option value="reference_only" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'reference_only')>
                                    Hanya No. Referensi (Contoh: Voucher)
                                </option>
                                <option value="proof_and_reference" @selected(old('required_fields_config', $paymentMethod->required_fields_config) == 'proof_and_reference')>
                                    Bukti Foto & No. Referensi (Contoh: Giro)
                                </option>
                            </select>
                            @error('required_fields_config')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- =============================================== --}}

                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="is_active" name="is_active" required>
                                <option value="1" @selected(old('is_active', $paymentMethod->is_active) == 1)>Aktif</option>
                                <option value="0" @selected(old('is_active', $paymentMethod->is_active) == 0)>Tidak Aktif</option>
                            </select>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Update Metode
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
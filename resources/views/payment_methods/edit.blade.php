@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit Metode Pembayaran: {{ $paymentMethod->name }}</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('payment-methods.update', $paymentMethod->payment_method_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nama Metode</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $paymentMethod->name) }}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label fw-semibold">Tipe Metode</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="direct" @selected(old('type', $paymentMethod->type) == 'direct')>Direct (Langsung Lunas oleh Admin: Cash, Transfer)</option>
                                <option value="pending" @selected(old('type', $paymentMethod->type) == 'pending')>Pending (Perlu Kliring: Giro, Cek)</option>
                                <option value="gateway" @selected(old('type', $paymentMethod->type) == 'gateway')>Gateway (Otomatis via Callback: Midtrans)</option>
                            </select>
                            <div class="form-text">Pilih 'Pending' jika Anda perlu memverifikasi dana masuk (kliring) di kemudian hari.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_true" value="1" @checked(old('is_active', $paymentMethod->is_active) == 1)>
                                <label class="form-check-label" for="is_active_true">Aktif (Bisa digunakan)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_false" value="0" @checked(old('is_active', $paymentMethod->is_active) == 0)>
                                <label class="form-check-label" for="is_active_false">Tidak Aktif (Disembunyikan)</label>
                            </div>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <form action="{{ route('payment-methods.destroy', $paymentMethod->payment_method_id) }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus metode ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-2"></i> Hapus
                            </button>
                        </form>
                        <div>
                            <a href="{{ route('payment-methods.index') }}" class="btn btn-light me-2">Batal</a>
                            <button type="submit" class="btn btn-primary" onclick="document.querySelector('form[action*=\'update\']').submit();">
                                Update Metode
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
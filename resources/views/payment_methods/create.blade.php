@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Tambah Metode Pembayaran Baru</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('payment-methods.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nama Metode</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: BCA Transfer, Giro Mandiri" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label fw-semibold">Tipe Metode</label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="direct" @selected(old('type') == 'direct')>Direct (Langsung Lunas oleh Admin: Cash, Transfer)</option>
                                <option value="pending" @selected(old('type') == 'pending')>Pending (Perlu Kliring: Giro, Cek)</option>
                                <option value="gateway" @selected(old('type') == 'gateway')>Gateway (Otomatis via Callback: Midtrans)</option>
                            </select>
                            <div class="form-text">Pilih 'Pending' jika Anda perlu memverifikasi dana masuk (kliring) di kemudian hari.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_true" value="1" checked>
                                <label class="form-check-label" for="is_active_true">Aktif (Bisa digunakan)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active" id="is_active_false" value="0">
                                <label class="form-check-label" for="is_active_false">Tidak Aktif (Disembunyikan)</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('payment-methods.index') }}" class="btn btn-light me-2">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Metode</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Tambah Role Baru</h3>
            <p class="text-muted mb-0 small">Buat peran pengguna baru dan tentukan hak aksesnya.</p>
        </div>
        <div>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                
                <div class="card card-transaction border-0 shadow-sm">
                    <div class="card-header bg-white p-4 border-bottom">
                        <div class="form-section-title mb-0"><i class="bi bi-shield-lock"></i> Konfigurasi Role</div>
                    </div>
                    
                    <div class="card-body p-4">
                        @include('roles._form')

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <a href="{{ route('roles.index') }}" class="btn btn-light border me-2">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Role</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
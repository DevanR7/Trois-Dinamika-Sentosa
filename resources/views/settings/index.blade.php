@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="fw-bold mb-4">Pengaturan Perusahaan</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="company_name" class="form-label fw-semibold">Nama Perusahaan</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="{{ $settings['company_name'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label for="company_owner" class="form-label fw-semibold">Nama Pemilik</label>
                        <input type="text" class="form-control" id="company_owner" name="company_owner" value="{{ $settings['company_owner'] ?? '' }}">
                    </div>
                    <div class="col-12">
                        <label for="company_address" class="form-label fw-semibold">Alamat</label>
                        <textarea class="form-control" id="company_address" name="company_address" rows="3">{{ $settings['company_address'] ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="company_city_province" class="form-label fw-semibold">Kota & Provinsi</label>
                        <input type="text" class="form-control" id="company_city_province" name="company_city_province" value="{{ $settings['company_city_province'] ?? '' }}">
                    </div>
                     <div class="col-md-6">
                        <label for="company_phone" class="form-label fw-semibold">No. Telepon</label>
                        <input type="text" class="form-control" id="company_phone" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label for="company_npwp" class="form-label fw-semibold">NPWP</label>
                        <input type="text" class="form-control" id="company_npwp" name="company_npwp" value="{{ $settings['company_npwp'] ?? '' }}">
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
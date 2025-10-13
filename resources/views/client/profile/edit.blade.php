@extends('layouts.client')

@section('content')
<div class="container-fluid py-4">
    <h2 class="fw-bold mb-4">Profil Saya</h2>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    
                    {{-- Form Update Informasi Profil --}}
                    <form method="post" action="{{ route('client.profile.update') }}">
                        @csrf
                        @method('patch')

                        <h5 class="fw-semibold border-bottom pb-2 mb-3">Informasi Akun</h5>
                        <div class="mb-3">
                            <label for="client_name" class="form-label">Nama Klien / Perusahaan</label>
                            <input id="client_name" name="client_name" type="text" class="form-control" value="{{ old('client_name', $client->client_name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="person_in_charge" class="form-label">Narahubung (PIC)</label>
                            <input id="person_in_charge" name="person_in_charge" type="text" class="form-control" value="{{ old('person_in_charge', $client->person_in_charge) }}">
                        </div>
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Nomor Telepon</label>
                            <input id="phone_number" name="phone_number" type="text" class="form-control" value="{{ old('phone_number', $client->phone_number) }}">
                        </div>
                         <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea id="address" name="address" class="form-control" rows="3">{{ old('address', $client->address) }}</textarea>
                        </div>

                        <h5 class="fw-semibold border-bottom pb-2 mt-4 mb-3">Ubah Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input id="current_password" name="current_password" type="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input id="password" name="password" type="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
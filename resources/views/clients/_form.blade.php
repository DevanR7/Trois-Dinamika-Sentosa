@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    {{-- INFORMASI DASAR --}}
    <div class="col-12">
        <h6 class="fw-bold text-dark mb-3">Informasi Perusahaan</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="client_name" class="form-label fw-bold small text-muted">NAMA KLIEN / PERUSAHAAN <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="client_name" name="client_name" value="{{ old('client_name', $client->client_name ?? '') }}" placeholder="Nama Lengkap Perusahaan" required>
            </div>
            <div class="col-md-6">
                <label for="person_in_charge" class="form-label fw-bold small text-muted">PENANGGUNG JAWAB (PIC)</label>
                <input type="text" class="form-control" id="person_in_charge" name="person_in_charge" value="{{ old('person_in_charge', $client->person_in_charge ?? '') }}" placeholder="Nama Kontak Person">
            </div>
        </div>
    </div>

    {{-- KONTAK --}}
    <div class="col-12">
        <h6 class="fw-bold text-dark mb-3 mt-2">Kontak & Alamat</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="email" class="form-label fw-bold small text-muted">EMAIL</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $client->email ?? '') }}" placeholder="email@perusahaan.com">
            </div>
            <div class="col-md-6">
                <label for="phone_number" class="form-label fw-bold small text-muted">NO. TELEPON / WHATSAPP</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $client->phone_number ?? '') }}" placeholder="0812...">
            </div>
            <div class="col-12">
                <label for="address" class="form-label fw-bold small text-muted">ALAMAT LENGKAP</label>
                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Jalan, Kota, Kode Pos...">{{ old('address', $client->address ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <hr class="border-dashed my-2">

    {{-- KEAMANAN (PASSWORD) --}}
    <div class="col-12">
        <h6 class="fw-bold text-dark mb-3">Keamanan Akun (Login Portal)</h6>
        <div class="alert alert-light border-0 bg-light text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i> 
            @if(isset($client))
                Kosongkan password jika tidak ingin mengubahnya.
            @else
                Password digunakan klien untuk login ke Client Portal.
            @endif
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="password" class="form-label fw-bold small text-muted">PASSWORD @if(!isset($client))<span class="text-danger">*</span>@endif</label>
                <div class="input-group">
                    <input type="password" class="form-control border-end-0" id="password" name="password" {{ isset($client) ? '' : 'required' }}>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="toggle-password" style="border-color: #ced4da;">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label fw-bold small text-muted">KONFIRMASI PASSWORD @if(!isset($client))<span class="text-danger">*</span>@endif</label>
                <div class="input-group">
                    <input type="password" class="form-control border-end-0" id="password_confirmation" name="password_confirmation">
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="toggle-password-confirmation" style="border-color: #ced4da;">
                        <i class="bi bi-eye-slash"></i>
                    </button>
                </div>
                <div id="password-match-error" class="text-danger small mt-1 d-none"><i class="bi bi-exclamation-circle"></i> Password tidak cocok.</div>
            </div>
        </div>
    </div>
</div>
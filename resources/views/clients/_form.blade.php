@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    <div class="col-12">
        <label for="client_name" class="form-label fw-semibold">Nama Klien/Perusahaan</label>
        <input type="text" class="form-control" id="client_name" name="client_name" value="{{ old('client_name', $client->client_name ?? '') }}" required>
    </div>
    <div class="col-12">
        <label for="person_in_charge" class="form-label fw-semibold">Penanggung Jawab (PIC)</label>
        <input type="text" class="form-control" id="person_in_charge" name="person_in_charge" value="{{ old('person_in_charge', $client->person_in_charge ?? '') }}">
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $client->email ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="phone_number" class="form-label fw-semibold">Nomor Telepon</label>
        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $client->phone_number ?? '') }}">
    </div>
    <div class="col-12">
        <label for="address" class="form-label fw-semibold">Alamat</label>
        <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $client->address ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <label for="password" class="form-label fw-semibold">Password</label>
        <input type="password" class="form-control" id="password" name="password" {{ isset($client) ? '' : 'required' }}>
        @if(isset($client))<small class="text-muted">Kosongkan jika tidak ingin mengubah password.</small>@endif
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
    </div>
</div>
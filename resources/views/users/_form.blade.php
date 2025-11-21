@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<h6 class="fw-bold text-dark mb-3">Informasi Login & Peran</h6>
<div class="row g-4">
    {{-- Info Utama --}}
    <div class="col-md-6">
        <label for="full_name" class="form-label fw-semibold small text-muted">NAMA LENGKAP <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="full_name" name="full_name" value="{{ old('full_name', $user->full_name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="role" class="form-label fw-semibold small text-muted">PERAN (ROLE) <span class="text-danger">*</span></label>
        <select class="form-select" id="role" name="role" required>
            <option value="" disabled selected>Pilih Role...</option>
            @foreach ($roles as $role)
            <option 
                value="{{ $role->name }}" 
                @if(isset($user)) 
                    @selected($user->hasRole($role->name)) 
                @endif
            >
                {{ Str::title($role->name) }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="username" class="form-label fw-semibold small text-muted">USERNAME <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $user->username ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold small text-muted">EMAIL <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    
    {{-- Kode Sales (Conditional) --}}
    <div class="col-md-6" id="sales-code-container" style="display: none;">
        <label for="sales_code" class="form-label fw-semibold small text-muted">KODE SALES (Contoh: KV)</label>
        <input type="text" class="form-control" id="sales_code" name="sales_code" value="{{ old('sales_code', $user->sales_code ?? '') }}">
    </div>
</div>

<hr class="border-dashed my-4">

<h6 class="fw-bold text-dark mb-3">Informasi Tambahan</h6>
<div class="row g-4">
    <div class="col-md-6">
        <label for="nik" class="form-label fw-semibold small text-muted">NIK (OPSIONAL)</label>
        <input type="text" class="form-control @error('nik') is-invalid @enderror" id="nik" name="nik" value="{{ old('nik', $user->nik ?? '') }}">
        @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="phone_number" class="form-label fw-semibold small text-muted">NO. TELEPON (OPSIONAL)</label>
        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number ?? '') }}">
        @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="address" class="form-label fw-semibold small text-muted">ALAMAT (OPSIONAL)</label>
        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $user->address ?? '') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<hr class="border-dashed my-4">

<h6 class="fw-bold text-dark mb-3">Keamanan</h6>
<div class="row g-4">
    {{-- Kolom Password --}}
    <div class="col-md-6">
        <label for="password" class="form-label fw-semibold small text-muted">PASSWORD @if(!isset($user))<span class="text-danger">*</span>@endif</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" {{ isset($user) ? '' : 'required' }}>
            <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                <i class="bi bi-eye-slash"></i>
            </button>
        </div>
        @if(isset($user))<small class="text-muted">Kosongkan jika tidak ingin mengubah password.</small>@endif
    </div>

    {{-- Kolom Konfirmasi Password --}}
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label fw-semibold small text-muted">KONFIRMASI PASSWORD @if(!isset($user))<span class="text-danger">*</span>@endif</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" {{ isset($user) ? '' : 'required' }}>
            <button class="btn btn-outline-secondary" type="button" id="toggle-password-confirmation">
                <i class="bi bi-eye-slash"></i>
            </button>
        </div>
        <div id="password-match-error" class="text-danger small mt-1 d-none"><i class="bi bi-exclamation-circle"></i> Password tidak cocok.</div>
    </div>
</div>
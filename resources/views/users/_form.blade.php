{{-- resources/views/users/_form.blade.php --}}

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    {{-- Info Utama --}}
    <div class="col-12">
        <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="full_name" name="full_name" value="{{ old('full_name', $user->full_name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $user->username ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label for="role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
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
    <div class="col-md-6" id="sales-code-container" style="display: none;">
        <label for="sales_code" class="form-label fw-semibold">Kode Sales (Contoh: KV)</label>
        <input type="text" class="form-control" id="sales_code" name="sales_code" value="{{ old('sales_code', $user->sales_code ?? '') }}">
    </div>
    
    {{-- ✅ START: INFORMASI TAMBAHAN (OPSIONAL) --}}
    <div class="col-12"><hr class="my-3"></div>

    <div class="col-md-6">
        <label for="nik" class="form-label fw-semibold">NIK (Opsional)</label>
        <input type="text" class="form-control @error('nik') is-invalid @enderror" 
               id="nik" name="nik" value="{{ old('nik', $user->nik ?? '') }}">
        @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="phone_number" class="form-label fw-semibold">No. Telepon (Opsional)</label>
        <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
               id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number ?? '') }}">
        @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="address" class="form-label fw-semibold">Alamat (Opsional)</label>
        <textarea class="form-control @error('address') is-invalid @enderror" 
                  id="address" name="address" rows="3">{{ old('address', $user->address ?? '') }}</textarea>
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    
    <div class="col-12"><hr class="my-3"></div>
    {{-- ✅ END: INFORMASI TAMBAHAN --}}


    {{-- Kolom Password --}}
    <div class="col-md-6">
        <label for="password" class="form-label fw-semibold">Password @if(!isset($user))<span class="text-danger">*</span>@endif</label>
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
        <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password @if(!isset($user))<span class="text-danger">*</span>@endif</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" {{ isset($user) ? '' : 'required' }}>
            <button class="btn btn-outline-secondary" type="button" id="toggle-password-confirmation">
                <i class="bi bi-eye-slash"></i>
            </button>
        </div>
        <div id="password-match-error" class="text-danger small mt-1 d-none">Password tidak cocok.</div>
    </div>
</div>
{{-- resources/views/users/_form.blade.php --}}

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
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

    {{-- Kolom Password dengan Tombol Hide/Unhide --}}
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

    {{-- Kolom Konfirmasi Password dengan Tombol Hide/Unhide --}}
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
@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <ul class="mb-0 small ps-3">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="mb-4">
    <label for="name" class="form-label fw-bold small text-muted">NAMA ROLE <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" placeholder="Contoh: Staff Gudang" required>
</div>

<hr class="border-dashed my-4">

<h6 class="fw-bold text-dark mb-3">Hak Akses (Permissions)</h6>

<div class="row g-4">
    @foreach ($permissions as $group => $permissionList)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border shadow-sm bg-light">
                <div class="card-header bg-white py-2 border-bottom fw-bold text-primary small text-uppercase">
                    {{ Str::title(str_replace('_', ' ', $group)) }}
                </div>
                <div class="card-body p-3">
                    @foreach($permissionList as $permission)
                        <div class="form-check mb-2">
                            <input class="form-check-input cursor-pointer" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{$permission->id}}"
                                @checked( in_array($permission->name, old('permissions', $roleHasPermissions ?? [])) )
                            >
                            <label class="form-check-label cursor-pointer small" for="perm-{{$permission->id}}">
                                {{ ucwords(str_replace(['-', '_'], ' ', $permission->name)) }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="mb-3">
    <label for="name" class="form-label fw-semibold">Nama Role</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" required>
</div>

<div class="mb-3">
    <h5 class="fw-semibold">Hak Akses (Permissions)</h5>
    @foreach ($permissions as $group => $permissionList)
        <div class="mb-2">
            <strong>{{ Str::title($group) }}</strong>
            <div class="row">
                @foreach($permissionList as $permission)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{$permission->id}}"
                                @checked( in_array($permission->name, old('permissions', $roleHasPermissions ?? [])) )
                            >
                            <label class="form-check-label" for="perm-{{$permission->id}}">
                                {{ $permission->name }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
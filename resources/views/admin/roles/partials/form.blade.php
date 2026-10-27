<div class="form-group">
    <label for="name">Role Name <span class="text-danger">*</span></label>
    <input id="name" name="name" value="{{ old('name', $role?->name) }}" required
           class="form-control @error('name') is-invalid @enderror">
    @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
</div>

<div class="custom-control custom-checkbox mb-3">
    <input id="select-all" type="checkbox" class="custom-control-input">
    <label for="select-all" class="custom-control-label">Select all permissions</label>
</div>

@foreach($permissionGroups as $group => $permissions)
    <div class="border rounded p-3 mb-3">
        <strong>{{ $group }}</strong>
        <div class="row mt-2">
            @foreach($permissions as $permission)
                <div class="col-md-4 mb-2">
                    <div class="custom-control custom-checkbox">
                        <input id="permission_{{ $permission->id }}" name="permissions[]" type="checkbox"
                               value="{{ $permission->id }}" class="custom-control-input permission-checkbox"
                               @checked(in_array($permission->id, old('permissions', $selectedPermissions)))>
                        <label for="permission_{{ $permission->id }}" class="custom-control-label">
                            {{ str($permission->name)->after('.')->replace('-', ' ')->title() }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

@error('permissions')<div class="text-danger mb-3">{{ $message }}</div>@enderror
@error('permissions.*')<div class="text-danger mb-3">{{ $message }}</div>@enderror

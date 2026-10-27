@php($editing = filled($user))

<div class="form-group row">
    <label class="col-md-2" for="employee_id">Employee ID <span class="text-danger">*</span></label>
    <div class="col-md-10">
        <input id="employee_id" name="employee_id" type="text" required
               value="{{ old('employee_id', $user?->employee_id) }}"
               class="form-control @error('employee_id') is-invalid @enderror">
        @error('employee_id')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-2" for="name">Name <span class="text-danger">*</span></label>
    <div class="col-md-10">
        <input id="name" name="name" type="text" required value="{{ old('name', $user?->name) }}"
               class="form-control @error('name') is-invalid @enderror">
        @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-2" for="email">Email <span class="text-danger">*</span></label>
    <div class="col-md-10">
        <input id="email" name="email" type="email" required value="{{ old('email', $user?->email) }}"
               class="form-control @error('email') is-invalid @enderror">
        @error('email')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-2" for="password">Password @unless($editing)<span class="text-danger">*</span>@endunless</label>
    <div class="col-md-5">
        <input id="password" name="password" type="password" {{ $editing ? '' : 'required' }}
               autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
        @error('password')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
        @if($editing)<small class="text-muted">Leave blank to keep the current password.</small>@endif
    </div>
    <div class="col-md-5">
        <input name="password_confirmation" type="password" {{ $editing ? '' : 'required' }}
               autocomplete="new-password" class="form-control" placeholder="Confirm password">
    </div>
</div>

<div class="form-group row">
    <label class="col-md-2" for="roles">Roles <span class="text-danger">*</span></label>
    <div class="col-md-10">
        <select id="roles" name="roles[]" class="form-control select-role @error('roles') is-invalid @enderror" multiple required>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(in_array($role->id, old('roles', $selectedRoleIds)))>
                    {{ str($role->name)->replace('-', ' ')->title() }}
                </option>
            @endforeach
        </select>
        @error('roles')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
        @error('roles.*')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-2" for="is_active">Status <span class="text-danger">*</span></label>
    <div class="col-md-10">
        <select id="is_active" name="is_active" class="form-control" required>
            <option value="1" @selected((int) old('is_active', $user?->is_active ?? 1) === 1)>Active</option>
            <option value="0" @selected((int) old('is_active', $user?->is_active ?? 1) === 0)>Inactive</option>
        </select>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-2" for="profile_photo">Profile Photo</label>
    <div class="col-md-10">
        <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp"
               class="form-control-file upload-file @error('profile_photo') is-invalid @enderror"
               @if($editing) data-default-file="{{ $user->profile_photo_url }}" @endif>
        @error('profile_photo')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
    </div>
</div>

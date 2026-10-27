@extends('admin.master')
@section('title', 'Profile')

@push('styles')
    @include('admin.includes.dropify.dropify-css')
@endpush

@section('content')
    <div class="container-fluid">
        @include('admin.error.error-msg')
        <div class="card">
            <div class="card-header bg-transparent border-bottom">My Profile</div>
            <div class="card-body">
                <form action="{{ url('admin/update-profile') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="text-center mb-4">
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="rounded-circle" height="150" width="150">
                    </div>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="form-control @error('name') is-invalid @enderror">
                        @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="employee_id">Employee ID</label>
                        <input id="employee_id" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" required
                               class="form-control @error('employee_id') is-invalid @enderror">
                        @error('employee_id')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="profile_photo">Profile Photo</label>
                        <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp"
                               data-default-file="{{ $user->profile_photo_url }}"
                               class="form-control-file upload-file @error('profile_photo') is-invalid @enderror">
                        @error('profile_photo')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <button type="submit" class="btn btn-outline-secondary">Update</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.includes.dropify.dropify-js')
    <script>$(function () { $('.upload-file').dropify(); });</script>
@endpush

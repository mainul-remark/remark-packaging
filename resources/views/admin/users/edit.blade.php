@extends('admin.master')
@section('title', 'Edit User')

@push('styles')
    @include('admin.includes.dropify.dropify-css')
    @include('admin.includes.select2.select2-css')
@endpush

@section('content')
    <div class="container-fluid">
        @include('admin.error.error-msg')
        <div class="card">
            <div class="card-header bg-transparent border-bottom">
                <span><i class="fas fa-user-edit mr-2"></i>Edit User</span>
                <a class="btn btn-outline-secondary btn-sm float-right border-0" href="{{ route('users.index') }}">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.users.partials.form', compact('user', 'selectedRoleIds'))
                    <button type="submit" class="btn btn-outline-secondary">Update</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.includes.dropify.dropify-js')
    @include('admin.includes.select2.select2-js')
    @include('admin.partials.user.user-edit-script')
@endpush

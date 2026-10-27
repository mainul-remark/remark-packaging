@extends('admin.master')
@section('title', 'Create User')

@push('styles')
    @include('admin.includes.dropify.dropify-css')
    @include('admin.includes.select2.select2-css')
@endpush

@section('content')
    <div class="container-fluid">
        @include('admin.error.error-msg')
        <div class="card">
            <div class="card-header bg-transparent border-bottom">
                <span><i class="fas fa-plus-circle mr-2"></i>Create User</span>
                <a class="btn btn-outline-secondary btn-sm float-right border-0" href="{{ route('users.index') }}">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('admin.users.partials.form', ['user' => null, 'selectedRoleIds' => []])
                    <button type="submit" class="btn btn-outline-secondary">Create</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.includes.dropify.dropify-js')
    @include('admin.includes.select2.select2-js')
    @include('admin.partials.user.user-create-script')
@endpush

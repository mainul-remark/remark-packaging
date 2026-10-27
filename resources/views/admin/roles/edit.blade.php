@extends('admin.master')
@section('title', 'Edit Role')

@section('content')
    <div class="container-fluid">
        @include('admin.error.error-msg')
        <div class="card">
            <div class="card-header bg-transparent border-bottom">Edit Role</div>
            <div class="card-body">
                <form action="{{ route('roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('admin.roles.partials.form', compact('role', 'selectedPermissions'))
                    <button type="submit" class="btn btn-outline-secondary">Update</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.roles.partials.permission-script')
@endpush

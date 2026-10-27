@extends('admin.master')
@section('title', 'Create Role')

@section('content')
    <div class="container-fluid">
        @include('admin.error.error-msg')
        <div class="card">
            <div class="card-header bg-transparent border-bottom">Create Role</div>
            <div class="card-body">
                <form action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    @include('admin.roles.partials.form', ['role' => null, 'selectedPermissions' => []])
                    <button type="submit" class="btn btn-outline-secondary">Create</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.roles.partials.permission-script')
@endpush

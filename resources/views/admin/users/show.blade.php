@extends('admin.master')
@section('title', 'User')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-transparent border-bottom">
                User Details
                <a class="btn btn-outline-secondary btn-sm float-right border-0" href="{{ route('users.index') }}">Back</a>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="rounded-circle" height="150" width="150">
                </div>
                <table class="table table-bordered">
                    <tr><th>Employee ID</th><td>{{ $user->employee_id }}</td></tr>
                    <tr><th>Name</th><td>{{ $user->name }}</td></tr>
                    <tr><th>Email</th><td>{{ $user->email }}</td></tr>
                    <tr><th>Roles</th><td>
                        @forelse($user->roles as $role)
                            <span class="badge badge-secondary px-2 rounded-pill">{{ str($role->name)->replace('-', ' ')->title() }}</span>
                        @empty
                            <span class="text-muted">No role assigned</span>
                        @endforelse
                    </td></tr>
                    <tr><th>Status</th><td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td></tr>
                    <tr><th>Created</th><td>{{ $user->created_at?->format('Y-m-d H:i:s') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
@endsection

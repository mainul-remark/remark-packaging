@extends('admin.master')
@section('title', 'Roles')

@push('styles')
    @include('admin.includes.datatable-style')
@endpush

@section('content')
    <div class="container-fluid">
        @include('admin.error.error-msg')
        <div class="card">
            <div class="card-header bg-transparent border-bottom">
                <span><i class="fa fa-list mr-1"></i>Role List</span>
                @can('roles.create')
                    <a href="{{ route('roles.create') }}" class="btn btn-outline-secondary btn-sm float-right">
                        <i class="fa fa-plus-circle"></i> Create
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="roleDataTable">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Permissions</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ str($row->name)->replace('-', ' ')->title() }}</td>
                            <td><span class="badge badge-outline-blue px-2">{{ $row->permissions_count }}</span></td>
                            <td>{{ $row->created_at?->format('Y-m-d') }}</td>
                            <td>
                                @can('roles.update')
                                    <a href="{{ route('roles.edit', $row) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('roles.delete')
                                    <form action="{{ route('roles.destroy', $row) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" title="Delete"
                                                onclick="return confirm('Delete this role?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.includes.datatable-js')
    <script>$(function () { $('#roleDataTable').DataTable({ order: [[2, 'desc']] }); });</script>
@endpush

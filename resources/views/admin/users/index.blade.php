@extends('admin.master')
@section('title','User')
@push('styles')
    @include('admin.includes.datatable-style')
    <link href="{{asset('backend/assets/css/custom-badge.css')}}" rel="stylesheet"/>
    <style>
        .dataTables_processing {
            display: none !important;
        }
    </style>
@endpush
@section('content')
    <div class="page-breadcrumb border-bottom">
        <div class="row mb-2">
            <div class="col-5 align-self-center">
                <h5 class="page-title text-uppercase" style="font-family: 'Bell MT';font-size: 16px"><i class="mdi mdi-checkbox-marked-outline mr-2 "></i>Users</h5>
            </div>
            <div class="col-7 align-self-center">
                <div class="d-flex align-items-center justify-content-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Users</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">

                    <div class="card-header bg-transparent border-bottom">
                        <a class="text-uppercase"><i class="fa fa-list mr-1"></i> User List </a>
                        @can('users.create')
                            <a href="{{route('users.create')}}" class="btn btn-outline-secondary btn-sm float-right"> <i class="fa fa-plus-circle"></i> Create</a>
                        @endcan
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered w-100" id="userDatatable"></table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.includes.datatable-js')
    @include('admin.partials.user.user-index-script')
@endpush

@extends('admin.master')
@section('title','Activity Log')
@push('styles')
    <link href="{{asset('backend/assets/css/custom-badge.css')}}" rel="stylesheet"/>
    <link href="{{asset('backend/assets/css/custom-btn.css')}}" rel="stylesheet"/>
    @include('admin.includes.datatable-style')
@endpush

@section('content')
    <div class="page-breadcrumb border-bottom">
        <div class="row mb-2">
            <div class="col-5 align-self-center">
                <h5 class="page-title text-uppercase" style="font-family: 'Bell MT';font-size: 16px"><i class="mdi mdi-checkbox-marked-outline mr-2 "></i>Activity Log</h5>
            </div>
            <div class="col-7 align-self-center">
                <div class="d-flex align-items-center justify-content-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Activity Log</li>
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
                        <a class="text-uppercase"><i class="fa fa-list mr-1"></i> Activity Log List </a>

                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered w-100" id="activityLogDataTable">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Subject</th>
                                    <th>URL</th>
                                    <th>IP</th>
                                    <th>Agent</th>
                                    <th>Description</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
@push('scripts')

    @include('admin.includes.datatable-js')

    <script>
        $(document).ready(function () {

            $('#activityLogDataTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('admin/activity-log') }}",
                pageLength: 25,
                order: [[0, 'desc']],

                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'user', name: 'user', orderable: false, searchable: false },
                    { data: 'activity', name: 'event', orderable: false },
                    { data: 'module', name: 'log_name' },
                    { data: 'subject', name: 'subject_id', orderable: false, searchable: false },
                    { data: 'url', name: 'url', orderable: false, searchable: false },
                    { data: 'ip_address', name: 'ip_address', orderable: false, searchable: false },
                    { data: 'device_browser', name: 'device_browser', orderable: false, searchable: false },
                    { data: 'description', name: 'description' },
                ]
            });

        });
    </script>



@endpush



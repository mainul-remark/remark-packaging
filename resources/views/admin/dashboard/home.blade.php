@extends('admin.master')
@section('title','Dashboard')
@section('content')
    <div class="page-breadcrumb border-bottom mb-2">
        <div class="row">
            <div class="col-5 align-self-center">
                <h5 class="page-title text-uppercase" style="font-family: 'Bell MT';font-size: 16px"><i class="mdi mdi-checkbox-marked-outline mr-2 "></i>Dashboard</h5>
            </div>
            <div class="col-7 align-self-center py-2">
                <div class="d-flex align-items-center justify-content-end">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{url('/dashboard')}}">Home</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row pt-5">
            <div class="col-6 mx-auto">
                <div class="card border-0 p-3">
                    <div class="card-body text-center">
                        <h2 class="mb-2 text-dark">
                            <i class="mdi mdi-account mr-2"></i>
                            Welcome, {{ \Illuminate\Support\Facades\Auth::user()->name ?? '' }}
                        </h2>
                        <p class="text-bold small mb-0">We're glad to have you back.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

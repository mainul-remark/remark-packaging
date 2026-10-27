@extends('admin.master')
@section('title','Password')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-8 mx-auto">
                @include('admin.error.error-msg')
            </div>
        </div>
        <div class="row mt-lg-5">
            <div class="col-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-transparent border-bottom">
                        <a><i class="fas fa-plus-circle mr-2"></i>Change Password Form</a>
                        <a class="btn btn-outline-secondary float-right btn-sm border-0" href="{{url('admin/users')}}">
                            <i class="mdi mdi-arrow-left-bold-circle"></i> Back </a>
                    </div>
                    <div class="card-body">

                        <form class="form-horizontal m-t-20" action="{{url('admin/update-password')}}" method="POST">
                            @csrf
                            <div class="form-group row">
                                <label class="col-md-3 control-label">Old Password  <span class="text-danger"> * </span></label>
                                <div class="col-md-9">
                                    <input required type="password" class="form-control @error('old_password') is-invalid @enderror" name="old_password" value="{{ old('old_password') }}" placeholder="Old password"/>
                                    @error('old_password')
                                    <span class="invalid-feedback" role="alert">
                                            <strong>{{$message}}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="password" class="col-md-3">New Password <span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <input required type="password" name="password" id="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               placeholder="Password">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button"
                                                    id="togglePasswordBtn" aria-label="Show password">
                                                <i id="togglePasswordIcon" class="fas fa-eye-slash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    @error('password')
                                    <span class="invalid-feedback d-block"
                                          role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror

                                    <small id="passwordHelp" class="text-danger d-none"></small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="confirmPassword" class="col-md-3">Confirm Password <span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <input required id="confirmPassword" type="password"
                                           name="password_confirmation"
                                           class="form-control @error('password_confirmation') is-invalid @enderror"
                                           placeholder="Password confirmation">

                                    @error('password_confirmation')
                                    <span class="invalid-feedback d-block"
                                          role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror

                                    <small id="confirmHelp" class="text-danger d-none"></small>
                                </div>
                            </div>

                            <div class="form-group pt-3">
                                <div class="col-md-9 offset-3">
                                    <button type="submit" class="btn btn-secondary"><i
                                            class="mdi mdi-arrow-up-bold-circle mr-1"></i> Reset Password
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.partials.user.password-script')
@endpush


@extends('frontend.user.layouts.app')
@section('title', lang('Change Password', 'account'))
@section('page-pretitle', lang('Settings', 'account'))
@section('page-title', lang('Change Password', 'account'))
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <form id="deatilsForm" class="card" action="{{ route('user.settings.password.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">{{ lang('Password', 'forms') }}</label>
                        <input type="password" class="form-control" name="current-password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">{{ lang('New Password', 'forms') }}</label>
                        <input type="password" class="form-control" name="new-password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <div>
                        <label class="form-label required">{{ lang('Confirm New Password', 'forms') }}</label>
                        <input type="password" class="form-control" name="new-password_confirmation" autocomplete="new-password" minlength="8" required>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary">{{ lang('Save Changes', 'account') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

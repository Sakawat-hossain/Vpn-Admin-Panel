@extends('backend.layouts.form')
@section('section', admin_lang('Account'))
@section('title', admin_lang('Change password'))
@section('container', 'container-xxl flex-grow-1 container-p-y')
@section('content')
    <form id="billiongroup-submited-form" action="{{ route('admin.account.security.update') }}" method="POST">
        @csrf
        <div class="card p-2">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">{{ admin_lang('Password') }} : <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="current-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ admin_lang('New Password') }} : <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="new-password" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ admin_lang('Confirm New Password') }} : <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="new-password_confirmation" required>
                </div>
            </div>
        </div>
    </form>
@endsection

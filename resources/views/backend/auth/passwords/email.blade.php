@extends('backend.layouts.auth')
@section('title', admin_lang('Reset Password'))
@section('content')
<h3 class="mb-1">{{ admin_lang('Reset Password?') }} 🔒</h3>
<p class="mb-4">{{ admin_lang('Enter the email address associated with your account and we will send a link to reset your password.') }}</p>
<form id="formAuthentication" class="mb-3" action="{{ route('admin.password.reset') }}" method="POST">
  @csrf
  <div class="mb-3">
    <label for="email" class="form-label">{{ admin_lang('Email Address') }}</label>
    <input
      type="text"
      class="form-control"
      id="email"
      name="email"
      placeholder="Enter your email"
      autofocus />
  </div>
  {!! display_captcha() !!}
  <button class="btn btn-primary d-grid w-100">{{ admin_lang('Reset Password') }}</button>
</form>
<div class="text-center">
  <a href="{{ route('admin.login') }}" class="d-flex align-items-center justify-content-center">
    <i class="ti ti-chevron-left scaleX-n1-rtl"></i>
    {{ admin_lang('Login') }}
  </a>
</div>
@endsection

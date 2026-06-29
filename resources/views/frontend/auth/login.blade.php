@extends('frontend.layouts.auth')
@section('title', lang('Sign In', 'auth'))
@section('content')
<h3 class="mb-1">Login/Register</h3>
<p class="mb-4">{{ lang('Sign in to your account to continue', 'auth') }}</p>
<form id="formAuthentication" action="{{ route('login') }}" class="mb-3 fv-plugins-bootstrap5 fv-plugins-framework" method="POST">
  @csrf
  <div class="mb-3">
    <label for="email" class="form-label">{{ lang('Email Address') }}</label>
    <input
      type="email"
      class="form-control"
      id="email"
      name="email"
      placeholder="Enter your email"
      autofocus />
  </div>
  <div class="mb-3 form-password-toggle">
    <div class="d-flex justify-content-between">
      <label class="form-label" for="password">{{ lang('Password', 'forms') }}</label>
      <a href="{{ route('password.request') }}"><small>{{ lang('Forgot Your Password?', 'auth') }}</a></small>
    </div>
    <div class="input-group input-group-merge">
      <input
        type="password"
        id="password"
        class="form-control"
        name="password"
        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
        aria-describedby="password" />
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
  </div>
  <div class="mb-3">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} />
      <label class="form-check-label" for="remember-me"> {{ lang('Remember Me', 'auth') }} </label>
    </div>
  </div>
  {!! display_captcha() !!}
  <button class="btn btn-primary d-grid w-100">{{ lang('Sign In', 'auth')}} </button>
</form>
@if ($settings->actions->registration_status)
  <p class="text-center">
    <span>New on our platform?</span>
    <a href="{{ route('register') }}">
      <span>{{ lang('Sign Up', 'auth') }}</span>
    </a>
  </p>
@endif
{!! facebook_login() !!}
@endsection

@extends('backend.layouts.auth')
@section('title', admin_lang('Login'))
@section('content')
<h3 class="mb-1">Welcome to WG-Backend 👋</h3>
<p class="mb-4">Please sign-in to your account and start the adventure</p>
<form id="formAuthentication" class="mb-3" action="{{ route('admin.login.store') }}" method="POST">
  @csrf
  <div class="mb-3">
    <label for="email" class="form-label">{{ admin_lang('Email Address') }}</label>
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
      <label class="form-label" for="password">{{ admin_lang('Password') }}</label>
      <a href="{{ route('admin.password.reset') }}"><small>{{ admin_lang('Forgot password') }}?</a></small>
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
      <label class="form-check-label" for="remember-me"> {{ admin_lang('Remember me') }} </label>
    </div>
  </div>
  {!! display_captcha() !!}
  <button class="btn btn-primary d-grid w-100">{{ admin_lang('Login') }}</button>
</form>
@endsection

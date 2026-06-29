@extends('frontend.layouts.auth')
@section('title', lang('Sign Up', 'auth'))
@section('content')
    <div class="sign-box sign-box-wide">
        <h4>{{ lang('Sign Up', 'auth') }}</h4>
        <p class="text-muted fw-light mb-4">{{ lang('Enter your details to create an account.', 'auth') }}.</p>
        <form id="formAuthentication" action="{{ route('register') }}" class="mb-3 fv-plugins-bootstrap5 fv-plugins-framework" method="POST">
            @csrf
            <div class="row">
              <div class="mb-3 col-md-6 fv-plugins-icon-container">
                <label for="firstname" class="form-label">{{ lang('First Name', 'forms') }}</label>
                <input type="text" class="form-control" id="firstname" name="firstname"
                  placeholder="{{ lang('First Name', 'forms') }}" required>
              </div>
              <div class="mb-3 col-md-6 fv-plugins-icon-container">
                <label for="lastname" class="form-label">{{ lang('Last Name', 'forms') }}</label>
                <input type="text" class="form-control" id="lastname" name="lastname"
                  placeholder="{{ lang('Last Name', 'forms') }}" required>
              </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">{{ lang('Email address', 'forms') }}</label>
                <input type="email" name="email" id="email" class="form-control form-control-md" value="{{ old('email') }}"
                    placeholder="{{ lang('Email address', 'forms') }}" required>
            </div>
            <div class="mb-3 form-password-toggle">
                <div class="d-flex justify-content-between">
                    <label class="form-label" for="password">{{ lang('Password', 'forms') }}</label>
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
            <div class="mb-3 form-password-toggle">
                <div class="d-flex justify-content-between">
                    <label class="form-label" for="confirm-password">{{ lang('Confirm Password', 'forms') }}</label>
                </div>
                <div class="input-group input-group-merge">
                    <input
                        type="password"
                        id="confirm-password"
                        class="form-control"
                        name="password_confirmation"
                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                        aria-describedby="password" />
                    <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                </div>
            </div>
            @if ($settings->general->terms_of_service_link)
                <div class="mb-3">
                    <div class="form-check">
                        <input id="terms" name="terms" class="form-check-input" type="checkbox"
                            {{ old('terms') ? 'checked' : '' }} required>
                        <label class="form-check-label">
                            {{ lang('I agree to the', 'auth') }} <a href="{{ $settings->general->terms_of_service_link }}"
                                class="link link-primary">{{ lang('terms of service', 'auth') }}</a>
                        </label>
                    </div>
                </div>
            @endif
            {!! display_captcha() !!}
            <button class="btn btn-primary btn-md w-100">{{ lang('Sign Up', 'auth') }}</button>
        </form>
        {!! facebook_login() !!}
    </div>
@endsection

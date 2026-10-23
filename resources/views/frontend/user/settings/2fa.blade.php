@extends('frontend.user.layouts.app')
@section('title', lang('2FA Authentication', 'account'))
@section('page-pretitle', lang('Settings', 'account'))
@section('page-title', lang('2FA Authentication', 'account'))
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-secondary">{{ lang('2fa top description', 'account') }}</p>
                    <div class="row my-4">
                        <div class="col-md-8 m-auto">
                            @if (!$user->google2fa_status)
                                <div class="text-center mb-3">{!! $QR_Image !!}</div>
                                <div class="input-group mb-3">
                                    <input id="input-link" type="text" class="form-control" value="{{ $user->google2fa_secret }}" readonly>
                                    <button class="btn btn-icon btn-copy" type="button" data-clipboard-target="#input-link"
                                        title="{{ lang('Copy', 'account') }}"><i class="ti ti-copy"></i></button>
                                </div>
                                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal"
                                    data-bs-target="#towfactorModal">{{ lang('Enable 2FA Authentication', 'account') }}</button>
                            @else
                                <div class="text-center mb-3">
                                    <span class="badge bg-success-lt fs-4 p-2"><i class="ti ti-shield-check me-1"></i>{{ lang('Enabled', 'account') }}</span>
                                </div>
                                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                                    data-bs-target="#towfactorDisableModal">{{ lang('Disable 2FA Authentication', 'account') }}</button>
                            @endif
                        </div>
                    </div>
                    <p class="text-secondary mb-2">{{ lang('2fa bottom description', 'account') }}:</p>
                    <ul class="mb-0">
                        <li class="mb-1"><a target="_blank" rel="noopener"
                                href="https://apps.apple.com/us/app/google-authenticator/id388497605">{{ lang('Google Authenticator for iOS', 'account') }}</a></li>
                        <li class="mb-1"><a target="_blank" rel="noopener"
                                href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en&gl=US">{{ lang('Google Authenticator for Android', 'account') }}</a></li>
                        <li class="mb-1"><a target="_blank" rel="noopener"
                                href="https://apps.apple.com/us/app/microsoft-authenticator/id983156458">{{ lang('Microsoft Authenticator for iOS', 'account') }}</a></li>
                        <li><a target="_blank" rel="noopener"
                                href="https://play.google.com/store/apps/details?id=com.azure.authenticator&hl=en_US&gl=US">{{ lang('Microsoft Authenticator for Android', 'account') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @if (!$user->google2fa_status)
        <div class="modal modal-blur fade" id="towfactorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <form class="modal-content" action="{{ route('user.settings.2fa.enable') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label">{{ lang('OTP Code', 'forms') }}</label>
                        <input type="text" name="otp_code" class="form-control input-numeric" placeholder="••• •••"
                            maxlength="6" inputmode="numeric" autocomplete="one-time-code" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ lang('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ lang('Enable', 'account') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="modal modal-blur fade" id="towfactorDisableModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <form class="modal-content" action="{{ route('user.settings.2fa.disable') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label">{{ lang('OTP Code', 'forms') }}</label>
                        <input type="text" name="otp_code" class="form-control input-numeric" placeholder="••• •••"
                            maxlength="6" inputmode="numeric" autocomplete="one-time-code" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ lang('Close') }}</button>
                        <button type="submit" class="btn btn-danger">{{ lang('Disable', 'account') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

<div class="col-lg-4 col-xxl-3">
    <div class="card p-4">
        <div class="settings-side d-flex flex-column align-items-center">
            <div class="settings-user">
                <div class="avatar avatar-xl mb-3">
                    <img id="avatar_preview" class="rounded-circle" src="{{ asset(userAuthInfo()->avatar) }}" alt="{{ userAuthInfo()->name }}" />
                    @if (request()->routeIs('user.settings.index'))
                        <div class="settings-user-img-change">
                            <label for="change_avatar">
                                <i class="fa fa-camera"></i>
                            </label>
                        </div>
                    @endif
                </div>
                <div class="settings-user-title">
                    <p class="mb-0 h5 text-center">{{ userAuthInfo()->name }}</p>
                </div>
            </div>
            <div class="list-group w-100">
                <a href="{{ route('user.settings.index') }}"
                    class="list-group-item list-group-item-action d-flex justify-content-first {{ request()->routeIs('user.settings.index') ? 'active' : '' }}">
                    <i class="ti ti-edit me-2"></i>{{ lang('Account details', 'account') }}
                </a>
                <a href="{{ route('user.settings.subscription') }}"
                    class="list-group-item list-group-item-action d-flex justify-content-first {{ request()->routeIs('user.settings.subscription') ? 'active' : '' }}">
                    <i class="ti ti-diamond me-2"></i>{{ lang('My Subscription', 'account') }}
                </a>
                <a href="{{ route('user.settings.payment-history') }}"
                    class="list-group-item list-group-item-action d-flex justify-content-first {{ request()->routeIs('user.settings.payment-history') ? 'active' : '' }}">
                    <i class="ti ti-receipt me-2"></i>{{ lang('Payment History', 'account') }}
                </a>
                <a href="{{ route('user.settings.password') }}"
                    class="list-group-item list-group-item-action d-flex justify-content-first {{ request()->routeIs('user.settings.password') ? 'active' : '' }}">
                    <i class="ti ti-refresh me-2"></i>{{ lang('Change Password', 'account') }}
                </a>
                <a href="{{ route('user.settings.2fa') }}"
                    class="list-group-item list-group-item-action d-flex justify-content-first {{ request()->routeIs('user.settings.2fa') ? 'active' : '' }}">
                    <i class="ti ti-fingerprint me-2"></i>{{ lang('2FA Authentication', 'account') }}
                </a>
            </div>
        </div>
    </div>
</div>

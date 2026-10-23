@php
    $sidebarLinks = [
        ['route' => 'user.dashboard', 'icon' => 'ti-layout-dashboard', 'label' => lang('Dashboard', 'account')],
        ['route' => 'user.settings.subscription', 'icon' => 'ti-diamond', 'label' => lang('My Subscription', 'account')],
        ['route' => 'user.settings.payment-history', 'icon' => 'ti-receipt', 'label' => lang('Payment History', 'account')],
        ['route' => 'user.devices', 'icon' => 'ti-devices', 'label' => lang('Devices', 'account')],
    ];
    $settingsLinks = [
        ['route' => 'user.settings.index', 'icon' => 'ti-user-edit', 'label' => lang('Account details', 'account')],
        ['route' => 'user.settings.password', 'icon' => 'ti-lock', 'label' => lang('Change Password', 'account')],
        ['route' => 'user.settings.2fa', 'icon' => 'ti-fingerprint', 'label' => lang('2FA Authentication', 'account')],
    ];
@endphp
<aside class="navbar navbar-vertical navbar-expand-lg d-print-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-reset text-decoration-none">
                <img src="{{ asset($settings->media->light_logo) }}" alt="{{ $settings->general->site_name }}"
                    class="navbar-brand-image hide-theme-dark">
                <img src="{{ asset($settings->media->dark_logo) }}" alt="{{ $settings->general->site_name }}"
                    class="navbar-brand-image hide-theme-light">
            </a>
        </div>
        <div class="navbar-nav flex-row d-lg-none">
            @include('partials.theme-toggle', ['darkLabel' => lang('Dark mode', 'account'), 'lightLabel' => lang('Light mode', 'account')])
            @include('frontend.user.includes.user-dropdown')
        </div>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @foreach ($sidebarLinks as $link)
                    <li class="nav-item {{ request()->routeIs($link['route']) ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route($link['route']) }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti {{ $link['icon'] }} icon"></i></span>
                            <span class="nav-link-title">{{ $link['label'] }}</span>
                        </a>
                    </li>
                @endforeach
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('pricing') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-rocket icon"></i></span>
                        <span class="nav-link-title">{{ lang('Upgrade', 'account') }}</span>
                    </a>
                </li>
                <li class="nav-item mt-lg-3">
                    <div class="nav-link text-uppercase text-secondary small fw-bold pe-none">{{ lang('Settings', 'account') }}</div>
                </li>
                @foreach ($settingsLinks as $link)
                    <li class="nav-item {{ request()->routeIs($link['route']) ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route($link['route']) }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti {{ $link['icon'] }} icon"></i></span>
                            <span class="nav-link-title">{{ $link['label'] }}</span>
                        </a>
                    </li>
                @endforeach
                <li class="nav-item mt-lg-3">
                    <a class="nav-link" href="{{ route('home') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-world icon"></i></span>
                        <span class="nav-link-title">{{ lang('Back to website', 'account') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>

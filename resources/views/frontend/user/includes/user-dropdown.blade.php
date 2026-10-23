<div class="nav-item dropdown">
    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="{{ lang('Open user menu', 'account') }}">
        <span class="avatar avatar-sm" style="background-image: url('{{ asset(auth()->user()->avatar) }}')"></span>
        <div class="d-none d-xl-block ps-2">
            <div>{{ auth()->user()->name }}</div>
            <div class="mt-1 small text-secondary">{{ auth()->user()->email }}</div>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
        <a href="{{ route('user.dashboard') }}" class="dropdown-item"><i class="ti ti-layout-dashboard me-2"></i>{{ lang('Dashboard', 'account') }}</a>
        <a href="{{ route('user.settings.index') }}" class="dropdown-item"><i class="ti ti-settings me-2"></i>{{ lang('Settings', 'account') }}</a>
        <div class="dropdown-divider"></div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="dropdown-item"><i class="ti ti-power me-2"></i>{{ lang('Logout', 'auth') }}</button>
        </form>
    </div>
</div>

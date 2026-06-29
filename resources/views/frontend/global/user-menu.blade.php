<li class="nav-item dropdown me-2 me-xl-0">
  <a class="nav-link dropdown-toggle d-flex align-items-center" href="javascript:void(0);" data-bs-toggle="dropdown">
    <div class="pe-2">
      <img src="{{ asset(userAuthInfo()->avatar) }}" alt="{{ userAuthInfo()->name }}" class="user-img" width="25px">
    </div>
    <span class="d-none d-md-block">{{ userAuthInfo()->name }}</span>
    <i class="ti ti-angle-down ms-2"></i>
  </a>
  <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
    <li>
      <a href="{{ route('user.settings.index') }}"
        class="dropdown-item d-flex align-items-center {{ request()->segment(1) == 'settings' ? 'active' : '' }}">
        <i class="ti ti-settings pe-2"></i>
        <span>{{ lang('Settings', 'account') }}</span>
      </a>
    </li>
    <li>
      <a href="javascript:void()" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
        class="dropdown-item d-flex align-items-center">
        <i class="ti ti-power pe-2"></i>
        <span>{{ lang('Logout', 'auth') }}</span>
      </a>
    </li>
  </ul>
</li>
<form id="logout-form" class="d-inline" action="{{ route('logout') }}" method="POST">
  @csrf
</form>
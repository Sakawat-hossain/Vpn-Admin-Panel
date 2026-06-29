<nav
  class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
  id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="ti ti-menu-2 ti-sm"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <div class="navbar-nav align-items-center">
      <div class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <i class="ti ti-md"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-start dropdown-styles">
          <li>
            <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
              <span class="align-middle"><i class="ti ti-sun me-2"></i>Light</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
              <span class="align-middle"><i class="ti ti-moon me-2"></i>Dark</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
              <span class="align-middle"><i class="ti ti-device-desktop me-2"></i>System</span>
            </a>
          </li>
        </ul>
      </div>
    </div>

    <ul class="navbar-nav flex-row align-items-center ms-auto">
      <li class="nav-item me-3 me-xl-1">
        <a class="nav-link hide-arrow" href="{{ url('/') }}" target="_blank">
          <i class="ti ti-arrow-forward"></i>
        </a>
      </li>
      <!-- Notification -->
      <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1">
        <a
          class="nav-link dropdown-toggle hide-arrow"
          href="javascript:void(0);"
          data-bs-toggle="dropdown"
          data-bs-auto-close="outside"
          aria-expanded="false">
          <i class="ti ti-bell ti-md"></i>
          <span class="badge bg-danger rounded-pill badge-notifications">{{ $unreadAdminNotificationsAll }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end py-0">
          <li class="dropdown-menu-header border-bottom">
            <div class="dropdown-header d-flex align-items-center py-3">
              <h5 class="text-body mb-0 me-auto">{{ admin_lang('Notifications') }} ({{ $unreadAdminNotificationsAll }})</h5>
              @if ($unreadAdminNotifications)
                <a
                  href="{{ route('admin.notifications.readall') }}"
                  class="dropdown-notifications-all text-body"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  title="{{ admin_lang('Mark All as Read') }}"
                  ><i class="ti ti-mail-opened fs-4"></i
                ></a>
              @else
                <span class="ms-auto text-muted">{{ admin_lang('Mark All as Read') }}</span>
              @endif
            </div>
          </li>
          <li class="dropdown-notifications-list scrollable-container">
            <ul class="list-group list-group-flush">
            @forelse ($adminNotifications as $adminNotification)
              @if ($adminNotification->link)
                <li class="list-group-item list-group-item-action dropdown-notifications-item">
                  <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                      <a href="{{ route('admin.notifications.view', hashid($adminNotification->id)) }}">
                        <div class="avatar">
                          <img src="{{ $adminNotification->image }}" alt="{{ $adminNotification->title }}" class="h-auto rounded-circle" />
                        </div>
                      </a>
                    </div>
                    <div class="flex-grow-1">
                      <a href="{{ route('admin.notifications.view', hashid($adminNotification->id)) }}">
                        <h6 class="mb-1">{{ shortertext($adminNotification->title, 30) }}</h6>
                        <small class="text-muted">{{ $adminNotification->created_at->diffforhumans() }}</small>
                      </a>
                    </div>
                    <div class="flex-shrink-0 dropdown-notifications-actions">
                      <a href="{{ route('admin.notifications.view', hashid($adminNotification->id)) }}" class="dropdown-notifications-read"
                      >
                      @if (!$adminNotification->status)
                        <span class="badge badge-dot"></span>
                      @endif
                      </a>
                    </div>
                  </div>
                </li>
              @else
                <li class="list-group-item list-group-item-action dropdown-notifications-item">
                  <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar">
                        <img src="{{ $adminNotification->image }}" alt="{{ $adminNotification->title }}" class="h-auto rounded-circle" />
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-1">{{ shortertext($adminNotification->title, 30) }}</h6>
                      <small class="text-muted">{{ $adminNotification->created_at->diffforhumans() }}</small>
                    </div>
                    <div class="flex-shrink-0 dropdown-notifications-actions">
                      @if (!$adminNotification->status)
                        <a href="javascript:void(0);">
                          <span class="badge badge-dot"></span>
                        </a>
                      @endif
                    </div>
                  </div>
                </li>
              @endif
            @empty
              <li class="list-group-item list-group-item-action dropdown-notifications-item">
                <small class="text-muted mb-0">{{ admin_lang('No notifications found') }}</small>
              </li>
            @endforelse
            </ul>
          </li>
          <li class="dropdown-menu-footer border-top">
            <a
              href="{{ route('admin.notifications.index') }}"
              class="dropdown-item d-flex justify-content-center text-primary p-2 h-px-40 mb-1 align-items-center">
              {{ admin_lang('View All') }}
            </a>
          </li>
        </ul>
      </li>
      <!--/ Notification -->
      <!-- User -->
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <img src="{{ asset(adminAuthInfo()->avatar) }}" alt="{{ adminAuthInfo()->name }}" class="h-auto rounded-circle" />
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="#">
              <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar avatar-online">
                    <img src="{{ asset(adminAuthInfo()->avatar) }}" alt="{{ adminAuthInfo()->name }}" class="h-auto rounded-circle" />
                  </div>
                </div>
                <div class="flex-grow-1">
                  <span class="fw-medium d-block">{{ adminAuthInfo()->name }}</span>
                  <small class="text-muted">Admin</small>
                </div>
              </div>
            </a>
          </li>
          <li>
            <div class="dropdown-divider"></div>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('admin.account.details') }}">
              <i class="ti ti-user-check me-2 ti-sm"></i>
              <span class="align-middle">{{ admin_lang('Personal details') }}</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('admin.account.security') }}">
              <i class="ti ti-exchange me-2 ti-sm"></i>
              <span class="align-middle">{{ admin_lang('Change password') }}</span>
            </a>
          </li>
          <li>
            <div class="dropdown-divider"></div>
          </li>
          <li>
            <form action="{{ route('admin.logout') }}" method="POST">
              @csrf
              <button class="dropdown-item"><i
                class="ti ti-logout me-2 ti-sm"></i>{{ admin_lang('Logout') }}</button>
            </form>
          </li>
        </ul>
      </li>
      <!--/ User -->
    </ul>
  </div>
</nav>
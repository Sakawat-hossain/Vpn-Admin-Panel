<!-- Navbar: Start -->
<nav class="layout-navbar shadow-none py-0">
  <div class="container">
    <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-4">
      <!-- Menu logo wrapper: Start -->
      <div class="navbar-brand app-brand demo d-flex py-0 py-lg-2 me-4">
        <!-- Mobile menu toggle: Start-->
        <button class="navbar-toggler border-0 px-0 me-2" type="button" data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
          aria-label="Toggle navigation">
          <i class="ti ti-menu-2 ti-sm align-middle"></i>
        </button>
        <!-- Mobile menu toggle: End-->
        <a href="{{ route('home') }}" class="app-brand-link">
          <span class="app-brand-logo demo">
            <img src="{{ asset($settings->media->light_logo) }}" alt="{{ $settings->general->site_name }}" />
          </span>
          <span class="app-brand-text demo menu-text fw-bold">{{ $settings->general->site_name }}</span>
        </a>
      </div>
      <!-- Menu logo wrapper: End -->
      <!-- Menu wrapper: Start -->
      <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
        <button class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 scaleX-n1-rtl" type="button"
          data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
          aria-expanded="false" aria-label="Toggle navigation">
          <i class="ti ti-x ti-sm"></i>
        </button>
        <ul class="navbar-nav me-auto">
          @foreach ($navbarMenuLinks as $navbarMenuLink)
        <li class="nav-item">
        @if ($navbarMenuLink->children->count() > 0)
      <div class="btn-group">
        <a class="nav-link fw-medium dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown"
        aria-expanded="false">{{ $navbarMenuLink->name }}</a>
        <ul class="dropdown-menu" style="">
        @foreach ($navbarMenuLink->children as $child)
      <li>
      <a href="{{ $child->link }}" class="dropdown-item">
      <span>{{ $child->name }}</span>
      </a>
      </li>
    @endforeach
        </ul>
      </div>
    @else
    <a class="nav-link fw-medium" href="{{ $navbarMenuLink->link }}">
      <div class="link-title">
      <span>{{ $navbarMenuLink->name }}</span>
      </div>
    </a>
  @endif
        </li>
      @endforeach
        </ul>
      </div>
      <div class="landing-menu-overlay d-lg-none"></div>
      <!-- Menu wrapper: End -->
      <!-- Toolbar: Start -->
      <ul class="navbar-nav flex-row align-items-center ms-auto">
        @include('frontend.global.language-menu')
        <!-- Style Switcher -->
        <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <i class="ti ti-sm"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
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
        </li>
        <!-- / Style Switcher-->

        <!-- navbar button: Start -->
        @guest
      <li>
        <a href="{{ route('login') }}" class="btn btn-primary">
        <span class="tf-icons ti ti-login scaleX-n1-rtl me-md-1"></span>
        <span class="d-none d-md-block">Login/Register</span>
        </a>
      </li>
    @endguest
        @auth
      @include('frontend.global.user-menu')
    @endauth
        <!-- navbar button: End -->
      </ul>
      <!-- Toolbar: End -->
    </div>
  </div>
</nav>
<!doctype html>

<html
  lang="{{ getLang() }}"
  class="light-style layout-wide customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="/assets/"
  data-template="vertical-menu-template-starter">

<head>    
  @include('backend.includes.head')
  @include('backend.includes.styles')
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Helpers -->
  <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
  <script src="{{ asset('assets/vendor/js/template-customizer.js') }}"></script>
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="{{ asset('assets/js/config.js') }}"></script>
</head>

<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <!-- Menu -->
      @include('backend.includes.sidebar')
      <!-- / Menu -->

      <!-- Layout container -->
      <div class="layout-page">
        <!-- Navbar -->
        @include('backend.includes.header')
        <!-- / Navbar -->

        <!-- Content wrapper -->
        <div class="content-wrapper">
          <!-- Content -->

          <div class="@yield('container')">
            <div class="d-flex justify-content-between align-items-center">
              @include('backend.includes.breadcrumb')
              <div class="col-auto">
                @hasSection('back')
                  <a href="@yield('back')" class="btn btn-secondary me-2"><i
                    class="ti ti-arrow-left me-2"></i>{{ admin_lang('Back') }}</a>
                @endif
                @hasSection('language')
                  <div class="dropdown d-inline me-2">
                    <button class="btn btn-secondary dropdown-toggle" type="button"
                      id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ti ti-globe me-2"></i>{{ $active }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      @foreach ($adminLanguages as $adminLanguage)
                        <li><a class="dropdown-item @if ($adminLanguage->name == $active) active @endif"
                          href="?lang={{ $adminLanguage->code }}">{{ $adminLanguage->name }}</a>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
                @hasSection('modal')
                  <button type="button" class="btn btn-dark ms-2" data-bs-toggle="modal"
                    data-bs-target="#viewModal">
                    @yield('modal')
                  </button>
                @endif
                @hasSection('link')
                  <a href="@yield('link')" class="btn btn-primary ms-2"><i class="ti ti-plus"></i></a>
                @endif
                <button form="billiongroup-submited-form" class="btn btn-primary @yield('btn_action')"
                    @yield('btn_action')>{{ admin_lang('Save') }}</button>
              </div>
            </div>
            
            @yield('content')
          </div>
          <!-- / Content -->

          @include('backend.includes.footer')

          <div class="content-backdrop fade"></div>
        </div>
        <!-- Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
  </div>

  <!-- Toast with Placements -->
  <div
    class="bs-toast toast toast-placement-ex m-2"
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
    data-bs-delay="4000">
    <div class="toast-header">
      <i class="ti ti-bell ti-xs me-2"></i>
      <div class="me-auto fw-medium">Alert</div>
      <!-- <small class="text-muted">11 mins ago</small> -->
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body"></div>
  </div>
  <!-- Toast with Placements -->
  <!-- / Layout wrapper -->
  @include('backend.includes.scripts')
</body>

</html>
<!doctype html>

<html lang="{{ getLang() }}" class="light-style layout-navbar-fixed layout-wide" dir="ltr" data-theme="theme-default"
  data-assets-path="/assets/" data-template="front-pages">

<head>
  @include('frontend.global.head')
  @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/front-page-landing.css') }}" />
  @endpush
  @include('frontend.global.styles')
  <!-- Helpers -->
  <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
  <script src="{{ asset('assets/vendor/js/template-customizer.js') }}"></script>
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="{{ asset('assets/js/front-config.js') }}"></script>
</head>

<body>
  @include('frontend.includes.navbar')
  @yield('content')
  @include('frontend.includes.footer')
  @include('frontend.configurations.config')
  @include('frontend.configurations.widgets')
  @include('frontend.global.scripts')
</body>

</html>
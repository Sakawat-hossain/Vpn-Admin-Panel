<!doctype html>

<html
  lang="{{ getLang() }}"
  class="light-style layout-navbar-fixed layout-wide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="/assets/"
  data-template="front-pages">

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
    @include('frontend.user.includes.navbar')
    <!-- Sections:Start -->
    <div data-bs-spy="scroll" class="scrollspy-example">
      <section id="landingArticles" class="section-py b-articles" data-aos="fade-up" data-aos-duration="1000">
        <div class="container">
          <div class="row row-cols-auto justify-content-between g-2 mb-4">
            <div>
              @if (!request()->routeIs('checkout.proccess'))
                <h3 class="mb-2">@yield('title')</h3>
                @include('frontend.user.includes.breadcrumb')
              @endif
            </div>
            <div>
              @hasSection('back')
                <div class="col">
                  <a href="@yield('back')" class="btn btn-light btn-md px-3"><i
                    class="ti ti-arrow-left me-2"></i>{{ lang('back', 'account') }}</a>
                </div>
              @endif
            </div>
          </div>

          <div>
          @if (
            !request()->routeIs('checkout.index') &&
              auth()->user()->isSubscribed())
            @if (!auth()->user()->subscription->isCancelled())
              @if (auth()->user()->subscription->isAboutToExpire())
                <div class="alert alert-warning">
                  <h5 class="mb-3"><i
                    class="fa-solid fa-triangle-exclamation me-2"></i>{{ lang('Action Required!', 'account') }}
                  </h5>
                  <p class="mb-0">{{ lang('subscription about to expire notice', 'account') }}
                  </p>
                </div>
              @elseif(auth()->user()->subscription->isExpired())
                <div class="alert alert-danger">
                  <h5 class="mb-3"><i
                    class="fa-solid fa-triangle-exclamation me-2"></i>{{ lang('Action Required!', 'account') }}
                  </h5>
                  <p class="mb-0">{{ lang('subscription expired notice', 'account') }}</p>
                </div>
              @endif
            @else
              <div class="alert alert-danger">
                <p class="mb-0"><i
                  class="fa-solid fa-triangle-exclamation me-2"></i>{{ lang('subscription cancelled notice', 'account') }}
                </p>
              </div>
            @endif
          @endif
          </div>

          <div class="row gy-4 mb-4">
            @yield('content')
          </div>
        </div>
      </section>
    </div>
    @include('frontend.user.includes.footer')
    @include('frontend.configurations.config')
    @include('frontend.configurations.widgets')
    @include('frontend.global.scripts')
</body>

</html>

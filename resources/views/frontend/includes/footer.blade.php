<!-- Footer: Start -->
<footer class="landing-footer bg-body footer-text">
  <div class="footer-top position-relative overflow-hidden z-1">
    @if ($footerMenuLinks->count() > 0)
      <img
        src="{{ asset('assets/img/front-pages/backgrounds/footer-bg-light.png') }}"
        alt="footer bg"
        class="footer-bg banner-bg-img z-n1"
        data-app-light-img="front-pages/backgrounds/footer-bg-light.png"
        data-app-dark-img="front-pages/backgrounds/footer-bg-dark.png" />
      <div class="container">
        <div class="row gx-0 gy-4 g-md-5">
          @foreach ($footerMenuLinks as $footerMenuLink)
            @if ($footerMenuLink->children->count() > 0)
              <div class="col-lg-2 col-md-4 col-sm-6">
                <h6 class="footer-title mb-4">{{ $footerMenuLink->name }}</h6>
                <ul class="list-unstyled">
                  @foreach ($footerMenuLink->children as $child)
                    <li class="mb-3">
                      <a href="{{ $child->link }}" class="footer-link">{{ $child->name }}</a>
                    </li>
                  @endforeach
                </ul>
              </div>
            @else
              <a href="{{ $footerMenuLink->link }}" class="footer-link">{{ $footerMenuLink->name }}</a>
            @endif
          @endforeach
          <div class="col-lg-4 col-md-4 d-flex justify-content-between">
            <div></div>
            <div>
              <h6 class="footer-title mb-4">Download our app</h6>
              <a href="javascript:void(0);" class="d-block footer-link mb-3 pb-2"
                ><img src="{{ asset('assets/img/front-pages/landing-page/apple-icon.png') }}" alt="apple icon"
              /></a>
              <a href="javascript:void(0);" class="d-block footer-link"
                ><img src="{{ asset('assets/img/front-pages/landing-page/google-play-icon.png') }}" alt="google play icon"
              /></a>
              
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom py-3">
      <div
        class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
        <div class="mb-2 mb-md-0">
          <span class="footer-text"
            >©
            <script>
              document.write(new Date().getFullYear());
            </script>
          </span>
          <a href="javascript:void(0)" target="_blank" class="fw-medium text-white footer-link">{{ $settings->general->site_name }},</a>
          <span class="footer-text"> {{ lang('All rights reserved') }}.</span>
        </div>
        <div>
          <a href="javascript:void(0)" class="footer-link me-3" target="_blank">
            <img
              src="{{ asset('assets/img/front-pages/icons/github-light.png') }}"
              alt="github icon"
              data-app-light-img="front-pages/icons/github-light.png"
              data-app-dark-img="front-pages/icons/github-dark.png" />
          </a>
          <a href="javascript:void(0)" class="footer-link me-3" target="_blank">
            <img
              src="{{ asset('assets/img/front-pages/icons/facebook-light.png') }}"
              alt="facebook icon"
              data-app-light-img="front-pages/icons/facebook-light.png"
              data-app-dark-img="front-pages/icons/facebook-dark.png" />
          </a>
          <a href="javascript:void(0)" class="footer-link me-3" target="_blank">
            <img
              src="{{ asset('assets/img/front-pages/icons/twitter-light.png') }}"
              alt="twitter icon"
              data-app-light-img="front-pages/icons/twitter-light.png"
              data-app-dark-img="front-pages/icons/twitter-dark.png" />
          </a>
          <a href="javascript:void(0)" class="footer-link" target="_blank">
            <img
              src="{{ asset('assets/img/front-pages/icons/instagram-light.png') }}"
              alt="google icon"
              data-app-light-img="front-pages/icons/instagram-light.png"
              data-app-dark-img="front-pages/icons/instagram-dark.png" />
          </a>
        </div>
      </div>
    @endif
  </div>
</footer>
<!-- Footer: End -->

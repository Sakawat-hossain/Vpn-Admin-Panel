<!-- Fun facts: Start -->
<section id="landingFunFacts" class="section-py landing-fun-facts" data-aos="fade-up" data-aos-duration="1000">
  <div class="container">
    <div class="row gy-3">
      <div class="col-sm-6 col-lg-3">
        <div class="card border border-label-primary shadow-none h-100">
          <div class="card-body text-center">
            <img src="{{ asset('assets/img/front-pages/icons/user.png') }}" alt="Premium User" class="mb-2" />
            <h5 class="h2 mb-1">{{ $settings->general->premium_users }}</h5>
            <p class="fw-medium mb-0">
              Premium Users
            </p>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card border border-label-success shadow-none h-100">
          <div class="card-body text-center">
            <img src="{{ asset('assets/img/front-pages/icons/user-success.png') }}" alt="User Joined" class="mb-2" />
            <h5 class="h2 mb-1">{{ $settings->general->joined_users }}</h5>
            <p class="fw-medium mb-0">
              Joined Users
            </p>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card border border-label-info shadow-none">
          <div class="card-body text-center">
            <img src="{{ asset('assets/img/front-pages/icons/diamond-info.png') }}" alt="Highly Rated Products" class="mb-2" />
            <h5 class="h2 mb-1">{{ $settings->general->highly_rated_products }}</h5>
            <p class="fw-medium mb-0">
              Highly Rated<br />
              Products
            </p>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="card border border-label-warning shadow-none h-100">
          <div class="card-body text-center">
            <img src="{{ asset('assets/img/front-pages/icons/check-warning.png') }}" alt="laptop" class="mb-2" />
            <h5 class="h2 mb-1">{{ $settings->general->money_free }}</h5>
            <p class="fw-medium mb-0">
              Free
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Fun facts: End -->
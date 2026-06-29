<!-- Pricing plans: Start -->
<section id="landingPricing" class="section-py bg-body landing-pricing">
  <div class="container">
    <div class="text-center mb-3 pb-1">
      <span class="badge bg-label-primary">Pricing Plans</span>
    </div>
    <h3 class="text-center mb-1">
      <span class="position-relative fw-bold z-1"
        >{{ lang('Choose Your Plan', 'home page') }}
      </span>
    </h3>
    <p class="text-center mb-4 pb-3">
      {{ lang('100% ANONYMOUS AND UNTRACEABLE.', 'home page') }}
    </p>
    <div class="plans">
      @if ($plans->count() > 0)
        <div class="plans-item">
          <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 justify-content-center align-items-stretch g-3">
              @foreach ($plans as $plan)
                <div class="card plan-card p-4 {{ $plan->isFeatured() ? 'bg-primary text-white' : '' }} me-4">
                  <div class="plan {{ $plan->isFeatured() ? 'pro' : '' }}">
                    <div class="plan-header">
                      <h3 class="{{ $plan->isFeatured() ? 'text-white' : '' }}">{{ $plan->name }}</h3>
                      <p>{{ $plan->product_id }}</p>
                    </div>
                    <div class="plan-body">
                      <hr/>
                      <div class="plan-price d-flex justify-content-center align-items-center">
                        @if ($plan->isFree())
                          <p class="mb-0">{{ lang('Free', 'plans') }}</p>
                        @else
                          <p class="mb-0">{{ priceSymbol($plan->price) }}</p>
                          <span>/{{ formatInterval($plan->interval) }}</span>
                        @endif
                      </div>
                      <hr/>
                    </div>
                    <div class="plan-footer">
                      {!! planButton($plan) !!}
                    </div>
                  </div>
                </div>
              @endforeach
              </div>
          </div>
        </div>
      @endif
    </div>
  </div>
</section>
<!-- Pricing plans: End -->
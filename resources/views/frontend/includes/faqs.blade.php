@if ($settings->actions->faqs_status && $faqs->count() > 0)
  <!-- FAQ: Start -->
  <section id="landingFAQ" class="section-py bg-body" data-aos="fade-up" data-aos-duration="1000">
    <div class="container">
      <div class="text-center mb-3 pb-1">
        <span class="badge bg-label-primary">{{ lang('FAQs', 'home page') }}</span>
      </div>
      <h3 class="text-center mb-1">
        Frequently asked
        <span class="position-relative fw-bold z-1"
          >questions
          <img
            src="{{ asset('assets/img/front-pages/icons/section-title-icon.png') }}"
            alt="laptop charging"
            class="section-title-img position-absolute object-fit-contain bottom-0 z-n1" />
        </span>
      </h3>
      <p class="text-center mb-5 pb-3">{{ lang('faqs description', 'home page') }}</p>
      <div class="row gy-5">
        <div class="col-lg-5">
          <div class="text-center">
            <img
              src="{{ asset('assets/img/front-pages/landing-page/faq-boy-with-logos.png') }}"
              alt="faq boy with logos"
              class="faq-image" />
          </div>
        </div>
        <div class="col-lg-7">
          <div class="accordion" id="accordionExample">
            @php
              $faqi=0;;
              $stats = ' active';
              $show = ' show';
            @endphp
            @foreach ($faqs as $faq)
              @php
                $faqi++;
                if ($faqi > 1) {
                  $stats = '';
                  $show = '';
                }
              @endphp
              <div class="card accordion-item{{ $stats }}">
                <h2 class="accordion-header" id="heading{{ hashid($faq->id) }}">
                  <button
                    type="button"
                    class="accordion-button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse{{ hashid($faq->id) }}"
                    aria-expanded="true"
                    aria-controls="collapse{{ hashid($faq->id) }}">
                    {{ $faq->title }}
                  </button>
                </h2>

                <div id="collapse{{ hashid($faq->id) }}" class="accordion-collapse collapse{{ $show }}" data-bs-parent="#accordionExample">
                  <div class="accordion-body">
                    {!! $faq->content !!}
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          <div class="d-flex justify-content-center mt-3">
            <a href="{{ route('faqs') }}"
              class="btn btn-primary btn-md">{{ lang('View More', 'home page') }}
              <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- FAQ: End -->
@endif

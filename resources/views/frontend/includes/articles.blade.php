@if ($settings->actions->blog_status && $blogArticles->count() > 0)
  <!-- FAQ: Start -->
  <section id="landingArticles" class="section-py b-articles" data-aos="fade-up" data-aos-duration="1000">
    <div class="container">
      <h3 class="text-center mb-1">
        {{ lang('Latest blog posts', 'home page') }}
      </h3>
      <p class="text-center mb-5 pb-3">{{ lang('blog section description', 'home page') }}</p>

      <div class="row gy-4 mb-4">
        @foreach ($blogArticles as $blogArticle)
          <div class="col-sm-6 col-lg-4">
            <div class="card p-2 h-100 shadow-none border">
              <div class="rounded-2 text-center mb-3">
                <a href="{{ route('blog.article', $blogArticle->slug) }}"
                  ><img
                    class="img-fluid"
                    src="{{ asset($blogArticle->image) }}" alt="{{ $blogArticle->title }}"
                /></a>
              </div>
              <div class="card-body p-3 pt-2">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="badge bg-label-primary">{{ dateFormat($blogArticle->created_at) }}</span>
                </div>
                <a href="{{ route('blog.article', $blogArticle->slug) }}" class="h5">{{ $blogArticle->title }}</a>
                <p class="mt-2">{{ $blogArticle->short_description }}</p>
                <div class="mt-2">
                  <a href="{{ route('blog.article', $blogArticle->slug) }}"
                    class="link link-primary">
                    {{ lang('Read More', 'home page') }} <i
                        class="ti ti-arrow-right fa-sm ms-1"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
        <div class="d-flex justify-content-center mt-5">
          <a href="{{ route('blog.index') }}"
            class="btn btn-primary btn-md">{{ lang('View More', 'home page') }}
            <i class="ti ti-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </section>
@endif

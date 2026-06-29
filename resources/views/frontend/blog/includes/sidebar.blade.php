<div class="col-12 col-xl-4" data-aos="fade-zoom-in" data-aos-duration="2000">
    <div class="card-v mb-4">
        <form action="{{ route('blog.index') }}" method="GET">
            <div class="form-search">
                <div class="input-group input-group-merge">
                    <span class="input-group-text" id="basic-addon-search31"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="{{ lang('Search…', 'blog') }}" aria-label="{{ lang('Search…', 'blog') }}" aria-describedby="basic-addon-search31" value="{{ request('search') ?? '' }}" required>
                </div>
            </div>
        </form>
    </div>
    {!! ads_blog_page_sidebar_top() !!}
    <div class="card-v mb-4">
        <h5 class="card-v-title mb-4">{{ lang('Categories', 'blog') }}</h5>
        <div class="categories">
            @forelse ($blogCategories as $blogCategory)
                <a href="{{ route('blog.category', $blogCategory->slug) }}" class="category link link-primary d-flex justify-content-between">
                    <span class="category-title">{{ $blogCategory->name }}</span>
                    <i class="ti ti-arrow-right"></i>
                </a>
            @empty
                <span class="text-muted">{{ lang('No categories found', 'blog') }}</span>
            @endforelse
        </div>
    </div>
    <div class="card-v">
        <h5 class="card-v-title mb-4">{{ lang('Popular Articles', 'blog') }}</h5>
        <div class="posts">
            @forelse ($popularBlogArticles as $popularBlogArticle)
                <div class="post">
                    <a href="{{ route('blog.article', $popularBlogArticle->slug) }}">
                        <img class="img-fluid" src="{{ asset($popularBlogArticle->image) }}"
                            alt="{{ $popularBlogArticle->title }}">
                    </a>
                    <div class="post-info">
                        <h6 class="post-title">
                            <a href="{{ route('blog.article', $popularBlogArticle->slug) }}"
                                class="link link-secondary">{{ $popularBlogArticle->title }}</a>
                        </h6>
                        <div class="post-meta">
                            <div class="post-meta-item">
                                <i class="ti ti-calendar"></i>
                                <time>{{ dateFormat($popularBlogArticle->created_at) }}</time>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <span class="text-muted text-center">{{ lang('No articles found', 'blog') }}</span>
            @endforelse
        </div>
    </div>
</div>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
  <div class="app-brand demo">
    <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo demo">
        <img src="{{ asset($settings->media->light_logo) }}" alt="{{ $settings->general->site_name }}" />
      </span>
      <span class="app-brand-text demo menu-text fw-bold">{{ $settings->general->site_name }}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <!-- Page -->
    <li class="menu-item {{ request()->segment(2) == 'dashboard' ? 'active' : '' }}">
      <a href="{{ route('admin.dashboard') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-app-window"></i>
        <div data-i18n="{{ admin_lang('Dashboard') }}">{{ admin_lang('Dashboard') }}</div>
      </a>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'users' ? 'active' : '' }}">
      <a href="{{ route('admin.users.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-users"></i>
        <div data-i18n="{{ admin_lang('Manage Users') }}">{{ admin_lang('Manage Users') }}</div>
        @if ($unviewedUsersCount)
          <div class="badge bg-danger rounded-pill ms-auto">{{ $unviewedUsersCount }}</div>
        @endif
      </a>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'servers' ? 'active' : '' }}">
      <a href="{{ route('admin.servers.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-server"></i>
        <div data-i18n="{{ admin_lang('Servers') }}">{{ admin_lang('Servers') }}</div>
      </a>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'subscriptions' ? 'active' : '' }}">
      <a href="{{ route('admin.subscriptions.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-diamond"></i>
        <div data-i18n="{{ admin_lang('Subscriptions') }}">{{ admin_lang('Subscriptions') }}</div>
      </a>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'transactions' ? 'active' : '' }}">
      <a href="{{ route('admin.transactions.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-arrows-exchange"></i>
        <div data-i18n="{{ admin_lang('Transactions') }}">{{ admin_lang('Transactions') }}</div>
      </a>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'plans' ? 'active' : '' }}">
      <a href="{{ route('admin.plans.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-tags"></i>
        <div data-i18n="{{ admin_lang('Pricing Plans') }}">{{ admin_lang('Pricing Plans') }}</div>
      </a>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'coupons' ? 'active' : '' }}">
      <a href="{{ route('admin.coupons.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-percentage"></i>
        <div data-i18n="{{ admin_lang('TranCoupon Codessactions') }}">{{ admin_lang('Coupon Codes') }}</div>
      </a>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'navigation' ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ti ti-navigation"></i>
        <div data-i18n="{{ admin_lang('Navigation') }}">{{ admin_lang('Navigation') }}</div>
        <div class="badge bg-primary rounded-pill ms-auto">2</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->segment(3) == 'navbarMenu' ? 'active' : '' }}">
          <a href="{{ route('admin.navbarMenu.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Navbar Menu') }}">{{ admin_lang('Navbar Menu') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'footerMenu' ? 'active' : '' }}">
          <a href="{{ route('admin.footerMenu.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Footer Menu') }}">{{ admin_lang('Footer Menu') }}</div>
          </a>
        </li>
      </ul>
    </li>
    @if ($settings->actions->blog_status)
    <li class="menu-item {{ request()->segment(2) == 'blog' ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ti ti-rss"></i>
        <div data-i18n="{{ admin_lang('Blog') }}">{{ admin_lang('Blog') }}</div>
        <div class="badge bg-primary rounded-pill ms-auto">3</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->segment(3) == 'articles' ? 'active' : '' }}">
          <a href="{{ route('articles.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Articles') }}">{{ admin_lang('Articles') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'categories' ? 'active' : '' }}">
          <a href="{{ route('categories.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Categories') }}">{{ admin_lang('Categories') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'comments' ? 'active' : '' }}">
          <a href="{{ route('comments.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Comments') }}">{{ admin_lang('Comments') }}</div>
            @if ($commentsNeedsAction)
              <div class="badge bg-danger rounded-pill ms-auto">{{ $commentsNeedsAction }}</div>
            @endif
          </a>
        </li>
      </ul>
    </li>
    @endif
    <li class="menu-item {{ request()->segment(2) == 'settings' ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ti ti-settings"></i>
        <div data-i18n="{{ admin_lang('Settings') }}">{{ admin_lang('Settings') }}</div>
        <div class="badge bg-primary rounded-pill ms-auto">9</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->segment(3) == 'general' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.general') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('General') }}">{{ admin_lang('General') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'smtp' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.smtp.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('SMTP') }}">{{ admin_lang('SMTP') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'pages' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.pages.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Pages') }}">{{ admin_lang('Pages') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'admins' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.admins.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Admins') }}">{{ admin_lang('Admins') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'languages' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.languages.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Languages') }}">{{ admin_lang('Languages') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'mailtemplates' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.mailtemplates.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Mail Templates') }}">{{ admin_lang('Mail Templates') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'seo' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.seo.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('SEO Configurations') }}">{{ admin_lang('SEO Configurations') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'gateways' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.gateways.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Payment Gateways') }}">{{ admin_lang('Payment Gateways') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'taxes' ? 'active' : '' }}">
          <a href="{{ route('admin.settings.taxes.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Taxes') }}">{{ admin_lang('Taxes') }}</div>
          </a>
        </li>
      </ul>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'others' ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ti ti-layers-subtract"></i>
        <div data-i18n="{{ admin_lang('Manage sections') }}">{{ admin_lang('Manage sections') }}</div>
        <div class="badge bg-primary rounded-pill ms-auto">2</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->segment(3) == 'features' ? 'active' : '' }}">
          <a href="{{ route('admin.features.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Home Features') }}">{{ admin_lang('Home Features') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'faqs' ? 'active' : '' }}">
          <a href="{{ route('admin.faqs.index') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('FAQs') }}">{{ admin_lang('FAQs') }}</div>
          </a>
        </li>
      </ul>
    </li>
    <li class="menu-item {{ request()->segment(2) == 'extra' ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ti ti-layers-subtract"></i>
        <div data-i18n="{{ admin_lang('Extra Features') }}">{{ admin_lang('Extra Features') }}</div>
        <div class="badge bg-primary rounded-pill ms-auto">2</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->segment(3) == 'popup-notice' ? 'active' : '' }}">
          <a href="{{ route('admin.extra.notice') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('PopUp Notice') }}">{{ admin_lang('PopUp Notice') }}</div>
          </a>
        </li>
        <li class="menu-item {{ request()->segment(3) == 'custom-css' ? 'active' : '' }}">
          <a href="{{ route('admin.extra.css') }}" class="menu-link">
            <div data-i18n="{{ admin_lang('Custom CSS') }}">{{ admin_lang('Custom CSS') }}</div>
          </a>
        </li>
      </ul>
    </li>
  </ul>
</aside>
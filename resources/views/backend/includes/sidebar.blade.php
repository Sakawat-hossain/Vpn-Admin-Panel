@php
    // segment(2) is the section after the admin path, segment(3) the sub-page.
    $section = request()->segment(2);
    $subPage = request()->segment(3);
    $menu = [
        ['label' => admin_lang('Dashboard'), 'icon' => 'ti-layout-dashboard', 'url' => route('admin.dashboard'), 'active' => $section == 'dashboard'],
        ['label' => admin_lang('Manage Users'), 'icon' => 'ti-users', 'url' => route('admin.users.index'), 'active' => $section == 'users', 'badge' => $unviewedUsersCount],
        ['label' => admin_lang('Servers'), 'icon' => 'ti-server-2', 'url' => route('admin.servers.index'), 'active' => $section == 'servers'],
        ['label' => admin_lang('Subscriptions'), 'icon' => 'ti-diamond', 'url' => route('admin.subscriptions.index'), 'active' => $section == 'subscriptions', 'badge' => $unviewedSubscriptions],
        ['label' => admin_lang('Transactions'), 'icon' => 'ti-arrows-exchange', 'url' => route('admin.transactions.index'), 'active' => $section == 'transactions', 'badge' => $unviewedTransactionsCount],
        ['label' => admin_lang('Pricing Plans'), 'icon' => 'ti-tags', 'url' => route('admin.plans.index'), 'active' => $section == 'plans'],
        ['label' => admin_lang('Coupon Codes'), 'icon' => 'ti-percentage', 'url' => route('admin.coupons.index'), 'active' => $section == 'coupons'],
        ['label' => admin_lang('Navigation'), 'icon' => 'ti-navigation', 'active' => $section == 'navigation', 'children' => [
            ['label' => admin_lang('Navbar Menu'), 'url' => route('admin.navbarMenu.index'), 'active' => $subPage == 'navbarMenu'],
            ['label' => admin_lang('Footer Menu'), 'url' => route('admin.footerMenu.index'), 'active' => $subPage == 'footerMenu'],
        ]],
    ];
    if ($settings->actions->blog_status) {
        $menu[] = ['label' => admin_lang('Blog'), 'icon' => 'ti-rss', 'active' => $section == 'blog', 'badge' => $commentsNeedsAction, 'children' => [
            ['label' => admin_lang('Articles'), 'url' => route('articles.index'), 'active' => $subPage == 'articles'],
            ['label' => admin_lang('Categories'), 'url' => route('categories.index'), 'active' => $subPage == 'categories'],
            ['label' => admin_lang('Comments'), 'url' => route('comments.index'), 'active' => $subPage == 'comments', 'badge' => $commentsNeedsAction],
        ]];
    }
    $menu[] = ['label' => admin_lang('Settings'), 'icon' => 'ti-settings', 'active' => $section == 'settings', 'children' => [
        ['label' => admin_lang('General'), 'url' => route('admin.settings.general'), 'active' => $subPage == 'general'],
        ['label' => admin_lang('SMTP'), 'url' => route('admin.settings.smtp.index'), 'active' => $subPage == 'smtp'],
        ['label' => admin_lang('Pages'), 'url' => route('admin.settings.pages.index'), 'active' => $subPage == 'pages'],
        ['label' => admin_lang('Admins'), 'url' => route('admin.settings.admins.index'), 'active' => $subPage == 'admins'],
        ['label' => admin_lang('Languages'), 'url' => route('admin.settings.languages.index'), 'active' => $subPage == 'languages'],
        ['label' => admin_lang('Mail Templates'), 'url' => route('admin.settings.mailtemplates.index'), 'active' => $subPage == 'mailtemplates'],
        ['label' => admin_lang('SEO Configurations'), 'url' => route('admin.settings.seo.index'), 'active' => $subPage == 'seo'],
        ['label' => admin_lang('Payment Gateways'), 'url' => route('admin.settings.gateways.index'), 'active' => $subPage == 'gateways'],
        ['label' => admin_lang('Taxes'), 'url' => route('admin.settings.taxes.index'), 'active' => $subPage == 'taxes'],
    ]];
    $menu[] = ['label' => admin_lang('Manage sections'), 'icon' => 'ti-layout-grid', 'active' => $section == 'others', 'children' => [
        ['label' => admin_lang('Home Features'), 'url' => route('admin.features.index'), 'active' => $subPage == 'features'],
        ['label' => admin_lang('FAQs'), 'url' => route('admin.faqs.index'), 'active' => $subPage == 'faqs'],
    ]];
    $menu[] = ['label' => admin_lang('Extra Features'), 'icon' => 'ti-puzzle', 'active' => $section == 'extra', 'children' => [
        ['label' => admin_lang('PopUp Notice'), 'url' => route('admin.extra.notice'), 'active' => $subPage == 'popup-notice'],
        ['label' => admin_lang('Custom CSS'), 'url' => route('admin.extra.css'), 'active' => $subPage == 'custom-css'],
    ]];
@endphp
<aside class="navbar navbar-vertical navbar-expand-lg d-print-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-reset text-decoration-none">
                <img src="{{ asset($settings->media->light_logo) }}" alt="{{ $settings->general->site_name }}"
                    class="navbar-brand-image hide-theme-dark">
                <img src="{{ asset($settings->media->dark_logo) }}" alt="{{ $settings->general->site_name }}"
                    class="navbar-brand-image hide-theme-light">
            </a>
        </div>
        <div class="navbar-nav flex-row d-lg-none">
            @include('partials.theme-toggle', ['darkLabel' => admin_lang('Dark mode'), 'lightLabel' => admin_lang('Light mode')])
            @include('backend.includes.admin-dropdown')
        </div>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @foreach ($menu as $item)
                    @if (empty($item['children']))
                        <li class="nav-item {{ $item['active'] ? 'active' : '' }}">
                            <a class="nav-link" href="{{ $item['url'] }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti {{ $item['icon'] }} icon"></i></span>
                                <span class="nav-link-title">{{ $item['label'] }}</span>
                                @if (!empty($item['badge']))
                                    <span class="badge bg-red text-red-fg ms-auto">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        </li>
                    @else
                        <li class="nav-item dropdown {{ $item['active'] ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle {{ $item['active'] ? 'show' : '' }}" href="#" data-bs-toggle="dropdown"
                                data-bs-auto-close="false" role="button" aria-expanded="{{ $item['active'] ? 'true' : 'false' }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti {{ $item['icon'] }} icon"></i></span>
                                <span class="nav-link-title">{{ $item['label'] }}</span>
                                @if (!empty($item['badge']))
                                    <span class="badge bg-red text-red-fg ms-auto me-2">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                            <div class="dropdown-menu {{ $item['active'] ? 'show' : '' }}">
                                @foreach ($item['children'] as $child)
                                    <a class="dropdown-item {{ $child['active'] ? 'active' : '' }}" href="{{ $child['url'] }}">
                                        {{ $child['label'] }}
                                        @if (!empty($child['badge']))
                                            <span class="badge bg-red text-red-fg ms-auto">{{ $child['badge'] }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</aside>

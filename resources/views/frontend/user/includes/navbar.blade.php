<header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
    <div class="container-xl">
        <div class="navbar-nav flex-row order-md-last ms-auto align-items-center">
            @if ($settings->actions->language_menu_status && $languages->count() > 1)
                <div class="nav-item dropdown me-2">
                    <a href="#" class="nav-link d-flex align-items-center gap-2" data-bs-toggle="dropdown"
                        aria-label="{{ lang('Language', 'account') }}">
                        <img src="{{ getLangFlag() }}" alt="{{ getLangName() }}" width="20" class="rounded-1">
                        <span>{{ getLangName() }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        @foreach ($languages as $language)
                            <a href="{{ langURL($language->code) }}"
                                class="dropdown-item {{ getLang() == $language->code ? 'active' : '' }}">
                                <img src="{{ asset($language->flag) }}" alt="{{ $language->name }}" width="20"
                                    class="rounded-1 me-2">{{ $language->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            @include('partials.theme-toggle', ['darkLabel' => lang('Dark mode', 'account'), 'lightLabel' => lang('Light mode', 'account')])
            @include('frontend.user.includes.user-dropdown')
        </div>
    </div>
</header>

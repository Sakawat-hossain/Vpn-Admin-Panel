@if ($settings->actions->language_menu_status)
  <li class="nav-item dropdown me-2 me-xl-0">
    <a class="nav-link dropdown-toggle d-flex align-items-center" href="javascript:void(0);" data-bs-toggle="dropdown">
      <div class="language-img pe-2">
          <img src="{{ getLangFlag() }}" alt="{{ getLangName() }}" width="25px"/>
      </div>
      <span class="d-none d-md-block">{{ getLangName() }}</span>
      <i class="ti ti-angle-down ms-2"></i>
    </a>
    <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
      @foreach ($languages as $language)
        <li>
          <a href="{{ langURL($language->code) }}"
            class="dropdown-item d-flex align-items-center {{ getLang() == $language->code ? 'active' : '' }}">
            <div class="language-img pe-2">
              <img src="{{ asset($language->flag) }}" alt="{{ $language->name }}" width="25px"/>
            </div>
            <span>{{ $language->name }}</span>
          </a>
        </li>
      @endforeach
    </ul>
  </li>
@endif

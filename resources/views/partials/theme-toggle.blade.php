{{-- Light / dark switch for the Tabler panels; behaviour in assets/js/panel-theme.js. --}}
<div class="nav-item me-2">
    <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" data-theme-switch="dark"
        title="{{ $darkLabel }}" data-bs-toggle="tooltip" data-bs-placement="bottom">
        <i class="ti ti-moon icon"></i>
    </a>
    <a href="?theme=light" class="nav-link px-0 hide-theme-light" data-theme-switch="light"
        title="{{ $lightLabel }}" data-bs-toggle="tooltip" data-bs-placement="bottom">
        <i class="ti ti-sun icon"></i>
    </a>
</div>

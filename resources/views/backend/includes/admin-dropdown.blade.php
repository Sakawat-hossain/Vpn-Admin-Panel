@php $admin = adminAuthInfo(); @endphp
<div class="nav-item dropdown">
    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="{{ admin_lang('Open user menu') }}">
        <span class="avatar avatar-sm" style="background-image: url('{{ asset($admin->avatar) }}')"></span>
        <div class="d-none d-xl-block ps-2">
            <div>{{ $admin->name }}</div>
            <div class="mt-1 small text-secondary">{{ admin_lang('Admin') }}</div>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
        <a class="dropdown-item" href="{{ route('admin.account.details') }}"><i class="ti ti-user-check me-2"></i>{{ admin_lang('Personal details') }}</a>
        <a class="dropdown-item" href="{{ route('admin.account.security') }}"><i class="ti ti-lock me-2"></i>{{ admin_lang('Change password') }}</a>
        <div class="dropdown-divider"></div>
        <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="dropdown-item"><i class="ti ti-logout me-2"></i>{{ admin_lang('Logout') }}</button>
        </form>
    </div>
</div>

<header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
    <div class="container-xl">
        <div class="navbar-nav flex-row order-md-last ms-auto align-items-center">
            <div class="nav-item me-2">
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="nav-link px-0"
                    title="{{ admin_lang('View website') }}" data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="ti ti-external-link icon"></i>
                </a>
            </div>
            @include('partials.theme-toggle', ['darkLabel' => admin_lang('Dark mode'), 'lightLabel' => admin_lang('Light mode')])
            <div class="nav-item dropdown me-3">
                <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-label="{{ admin_lang('Notifications') }}">
                    <i class="ti ti-bell icon"></i>
                    @if ($unreadAdminNotificationsAll)
                        <span class="badge bg-red text-red-fg badge-notification badge-pill">{{ $unreadAdminNotifications }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                    <div class="card">
                        <div class="card-header d-flex">
                            <h3 class="card-title">{{ admin_lang('Notifications') }} ({{ $unreadAdminNotificationsAll }})</h3>
                            @if ($unreadAdminNotificationsAll)
                                <a href="{{ route('admin.notifications.readall') }}" class="ms-auto small">{{ admin_lang('Mark All as Read') }}</a>
                            @endif
                        </div>
                        <div class="list-group list-group-flush list-group-hoverable admin-notifications">
                            @forelse ($adminNotifications as $adminNotification)
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="status-dot {{ $adminNotification->status ? '' : 'status-dot-animated bg-red' }} d-block"></span>
                                        </div>
                                        <div class="col-auto">
                                            <span class="avatar avatar-sm" style="background-image: url('{{ $adminNotification->image }}')"></span>
                                        </div>
                                        <div class="col text-truncate">
                                            @if ($adminNotification->link)
                                                <a href="{{ route('admin.notifications.view', hashid($adminNotification->id)) }}"
                                                    class="text-body d-block text-truncate">{{ shortertext($adminNotification->title, 40) }}</a>
                                            @else
                                                <div class="text-body d-block text-truncate">{{ shortertext($adminNotification->title, 40) }}</div>
                                            @endif
                                            <div class="d-block text-secondary text-truncate mt-n1 small">
                                                {{ $adminNotification->created_at->diffforhumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-secondary">{{ admin_lang('No notifications found') }}</div>
                            @endforelse
                        </div>
                        <div class="card-footer text-center">
                            <a href="{{ route('admin.notifications.index') }}">{{ admin_lang('View All') }}</a>
                        </div>
                    </div>
                </div>
            </div>
            @include('backend.includes.admin-dropdown')
        </div>
    </div>
</header>

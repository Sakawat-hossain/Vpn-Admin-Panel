@extends('frontend.user.layouts.app')
@section('title', lang('Dashboard', 'account'))
@section('page-pretitle', lang('Overview', 'account'))
@section('page-title', str_replace('{name}', $user->firstname ?: $user->name, lang('Welcome back, {name}', 'account')))
@section('page-actions')
    <a href="{{ route('pricing') }}" class="btn btn-primary">
        <i class="ti ti-rocket icon"></i>{{ lang('Upgrade', 'account') }}
    </a>
@endsection
@php
    $stateBadges = [
        'active' => ['bg-success-lt', lang('Active', 'account')],
        'free' => ['bg-azure-lt', lang('Free', 'account')],
        'expiring' => ['bg-warning-lt', lang('Expiring soon', 'account')],
        'expired' => ['bg-danger-lt', lang('Expired', 'account')],
        'cancelled' => ['bg-danger-lt', lang('Cancelled', 'account')],
        'none' => ['bg-secondary-lt', lang('No plan', 'account')],
    ];
    [$stateClass, $stateLabel] = $stateBadges[$subscription['state']];
    $plan = $subscription['plan'];
@endphp
@section('content')
    <div class="row row-deck row-cards">
        {{-- Stats --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-primary text-white avatar"><i class="ti ti-diamond icon"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $plan ? $plan->name : lang('No plan', 'account') }}</div>
                            <div class="text-secondary"><span class="badge {{ $stateClass }}">{{ $stateLabel }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-green text-white avatar"><i class="ti ti-calendar icon"></i></span></div>
                        <div class="col">
                            @if (in_array($subscription['state'], ['active', 'expiring']))
                                <div class="font-weight-medium">{{ str_replace('{days}', $subscription['daysLeft'], lang('{days} days left', 'account')) }}</div>
                                <div class="text-secondary">{{ dateFormat($subscription['expiry']) }}</div>
                            @elseif ($subscription['state'] === 'free')
                                <div class="font-weight-medium">{{ lang('Free plan', 'account') }}</div>
                                <div class="text-secondary">{{ lang('No expiry', 'account') }}</div>
                            @else
                                <div class="font-weight-medium">{{ lang('Not active', 'account') }}</div>
                                <div class="text-secondary">{{ $subscription['expiry'] ? dateFormat($subscription['expiry']) : '--' }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-azure text-white avatar"><i class="ti ti-server-2 icon"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $availableCount }} / {{ $servers->count() }}</div>
                            <div class="text-secondary">{{ lang('Servers available to you', 'account') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><span class="bg-purple text-white avatar"><i class="ti ti-world icon"></i></span></div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $countriesCount }}</div>
                            <div class="text-secondary">{{ lang('Countries', 'account') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Plan --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ lang('My Subscription', 'account') }}</h3>
                    <div class="card-actions">
                        <a href="{{ route('user.settings.subscription') }}" class="btn btn-ghost-secondary btn-sm">{{ lang('Details', 'account') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($plan)
                        <div class="d-flex align-items-center mb-3">
                            <div>
                                <div class="h2 mb-1">{{ $plan->name }}
                                    @if (formatInterval($plan->interval))
                                        <span class="text-secondary fs-4 fw-normal">({{ formatInterval($plan->interval) }})</span>
                                    @endif
                                </div>
                                <span class="badge {{ $stateClass }}">{{ $stateLabel }}</span>
                            </div>
                            @if (!$plan->isFree())
                                <div class="ms-auto text-end">
                                    <div class="text-secondary">{{ lang('Next bill date', 'account') }}</div>
                                    <div class="fw-bold {{ $subscription['state'] === 'expiring' ? 'text-warning' : ($subscription['state'] === 'expired' ? 'text-danger' : '') }}">
                                        {{ dateFormat($subscription['expiry']) }}
                                    </div>
                                </div>
                            @endif
                        </div>
                        @if (in_array($subscription['state'], ['active', 'expiring', 'expired']))
                            <div class="progress progress-sm mb-2">
                                <div class="progress-bar {{ $subscription['state'] === 'active' ? 'bg-success' : ($subscription['state'] === 'expiring' ? 'bg-warning' : 'bg-danger') }}"
                                    style="width: {{ $subscription['progress'] }}%" role="progressbar"
                                    aria-valuenow="{{ $subscription['progress'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="text-secondary mb-3">
                                {{ str_replace('{days}', $subscription['daysLeft'], lang('{days} days left', 'account')) }}
                            </div>
                        @elseif ($subscription['state'] === 'free')
                            <p class="text-secondary">{{ lang('You are on the free plan. Upgrade to unlock premium servers in more countries.', 'account') }}</p>
                        @endif
                        <div class="btn-list">
                            @if (in_array($subscription['state'], ['expiring', 'expired']))
                                <form action="{{ route('subscribe', [$plan->id, 'renew']) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-primary action-confirm"><i class="ti ti-refresh icon"></i>{{ lang('Renew', 'account') }}</button>
                                </form>
                            @endif
                            <a href="{{ route('pricing') }}" class="btn {{ in_array($subscription['state'], ['expiring', 'expired']) ? 'btn-outline-primary' : 'btn-primary' }}">
                                <i class="ti ti-arrow-big-up-lines icon"></i>{{ lang('Upgrade', 'account') }}
                            </a>
                        </div>
                    @else
                        <div class="empty py-4">
                            <div class="empty-icon"><i class="ti ti-diamond icon"></i></div>
                            <p class="empty-title">{{ lang('You are currently not subscribed', 'account') }}</p>
                            <p class="empty-subtitle text-secondary">{{ lang('To take advantage of exclusive benefits and features.', 'account') }}</p>
                            <div class="empty-action">
                                <a href="{{ route('pricing') }}" class="btn btn-primary">{{ lang('Subscribe Now', 'account') }}</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Account --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ lang('Account', 'account') }}</h3>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="avatar" style="background-image: url('{{ asset($user->avatar) }}')"></span></div>
                            <div class="col text-truncate">
                                <div class="text-reset d-block text-truncate">{{ $user->name }}</div>
                                <div class="text-secondary text-truncate mt-n1">{{ $user->email }}</div>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('user.settings.2fa') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="ti ti-fingerprint me-2"></i>{{ lang('2FA Authentication', 'account') }}
                        <span class="badge ms-auto {{ $user->google2fa_status ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                            {{ $user->google2fa_status ? lang('Enabled', 'account') : lang('Disabled', 'account') }}
                        </span>
                    </a>
                    <div class="list-group-item d-flex align-items-center">
                        <i class="ti ti-plug-connected me-2"></i>{{ lang('Last server', 'account') }}
                        <span class="ms-auto text-secondary text-truncate">
                            @if ($user->servers)
                                @if ($code = countryIsoCode($user->servers->country))
                                    <span class="fi fi-{{ $code }} server-flag server-flag-sm me-1"></span>
                                @endif
                                {{ $user->servers->country }}
                            @else
                                --
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('user.settings.password') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="ti ti-lock me-2"></i>{{ lang('Change Password', 'account') }}
                        <i class="ti ti-chevron-right ms-auto text-secondary"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Servers --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header flex-wrap gap-2">
                    <div>
                        <h3 class="card-title">{{ lang('Servers', 'account') }}</h3>
                        <p class="card-subtitle">{{ lang('Download an OpenVPN profile, or connect with our app.', 'account') }}</p>
                    </div>
                    <div class="card-actions server-search-wrap">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="search" class="form-control form-control-sm" id="server-search"
                                placeholder="{{ lang('Search country', 'account') }}" aria-label="{{ lang('Search country', 'account') }}">
                        </div>
                    </div>
                </div>
                @if ($servers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table" id="server-table">
                            <thead>
                                <tr>
                                    <th>{{ lang('Location', 'account') }}</th>
                                    <th class="d-none d-md-table-cell">{{ lang('Protocol', 'account') }}</th>
                                    <th class="d-none d-md-table-cell">{{ lang('Access', 'account') }}</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($servers as $server)
                                    @php $canUse = $user->canUseServer($server); @endphp
                                    <tr data-search="{{ strtolower($server->country . ' ' . $server->state) }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($code = countryIsoCode($server->country))
                                                    <span class="fi fi-{{ $code }} server-flag me-2"></span>
                                                @else
                                                    <i class="ti ti-world me-2 text-secondary"></i>
                                                @endif
                                                <div>
                                                    <div class="fw-medium">{{ $server->country }}
                                                        @if ($server->recommended)
                                                            <i class="ti ti-star-filled text-yellow ms-1" title="{{ lang('Recommended', 'account') }}"></i>
                                                        @endif
                                                    </div>
                                                    <div class="text-secondary small">{{ $server->state }}</div>
                                                    <div class="d-md-none mt-1">
                                                        @include('frontend.user.includes.server-badges', ['server' => $server])
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            @include('frontend.user.includes.server-badges', ['server' => $server, 'only' => 'protocol'])
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            @include('frontend.user.includes.server-badges', ['server' => $server, 'only' => 'access'])
                                        </td>
                                        <td class="text-nowrap">
                                            @if (!$canUse)
                                                <a href="{{ route('pricing') }}" class="btn btn-sm btn-outline-warning">
                                                    <i class="ti ti-lock icon"></i>{{ lang('Upgrade', 'account') }}
                                                </a>
                                            @elseif ($server->isOpenVpn())
                                                <a href="{{ route('user.servers.openvpn', $server->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-download icon"></i>.ovpn
                                                </a>
                                            @else
                                                <span class="text-secondary small"><i class="ti ti-device-mobile me-1"></i>{{ lang('Use our app', 'account') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="card-body text-center text-secondary d-none" id="server-search-empty">
                            {{ lang('No servers match your search.', 'account') }}
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="empty py-4">
                            <div class="empty-icon"><i class="ti ti-server-off icon"></i></div>
                            <p class="empty-title">{{ lang('No servers yet', 'account') }}</p>
                            <p class="empty-subtitle text-secondary">{{ lang('Servers will appear here as soon as they are online.', 'account') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- How to connect --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ lang('How to connect with OpenVPN', 'account') }}</h3>
                </div>
                <div class="card-body">
                    <ol class="connect-steps mb-3">
                        <li>{{ lang('Install an OpenVPN app on your device.', 'account') }}</li>
                        <li>{{ lang('Download the .ovpn profile of a server from the list above.', 'account') }}</li>
                        <li>{{ lang('Open the file with the app (or import it) and connect.', 'account') }}</li>
                        <li>{{ lang('If the app asks for a username and password, use your account email and password.', 'account') }}</li>
                    </ol>
                    <div class="btn-list">
                        <a href="https://openvpn.net/client/" target="_blank" rel="noopener" class="btn btn-sm">
                            <i class="ti ti-brand-windows icon"></i>Windows / macOS
                        </a>
                        <a href="https://apps.apple.com/app/openvpn-connect/id590379981" target="_blank" rel="noopener" class="btn btn-sm">
                            <i class="ti ti-brand-apple icon"></i>iPhone / iPad
                        </a>
                        <a href="https://play.google.com/store/apps/details?id=net.openvpn.openvpn" target="_blank" rel="noopener" class="btn btn-sm">
                            <i class="ti ti-brand-android icon"></i>Android
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent payments --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ lang('Recent payments', 'account') }}</h3>
                    <div class="card-actions">
                        <a href="{{ route('user.settings.payment-history') }}" class="btn btn-ghost-secondary btn-sm">{{ lang('View all', 'account') }}</a>
                    </div>
                </div>
                @if ($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td class="text-secondary">#{{ $transaction->id }}</td>
                                        <td>{{ dateFormat($transaction->created_at) }}</td>
                                        <td class="fw-medium">{{ priceSymbol($transaction->total) }}</td>
                                        <td class="text-end">
                                            @include('frontend.user.includes.transaction-status', ['transaction' => $transaction])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body">
                        <div class="empty py-3">
                            <div class="empty-icon"><i class="ti ti-receipt-off icon"></i></div>
                            <p class="empty-title">{{ lang('No payments yet', 'account') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

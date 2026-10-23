@extends('frontend.user.layouts.app')
@section('title', lang('Devices', 'account'))
@section('page-pretitle', lang('Account', 'account'))
@section('page-title', lang('Devices', 'account'))
@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ lang('Signed-in devices', 'account') }}</h3>
                <p class="card-subtitle">
                    @if ($limit > 0)
                        {{ str_replace(['{count}', '{limit}'], [$sessions->count(), $limit], lang('{count} of {limit} devices in use. Sign out a device you no longer use to sign in on a new one.', 'account')) }}
                    @else
                        {{ lang('Devices signed in to our apps. Sign out any device you no longer use.', 'account') }}
                    @endif
                </p>
            </div>
        </div>
        @if ($sessions->count() > 0)
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ lang('Device', 'account') }}</th>
                            <th class="d-none d-md-table-cell">{{ lang('Signed in', 'account') }}</th>
                            <th>{{ lang('Last active', 'account') }}</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-device-laptop me-2 text-secondary"></i>
                                        <div>
                                            <div class="fw-medium">{{ $session->device_name ?: lang('Unknown device', 'account') }}</div>
                                            @if ($session->ip)
                                                <div class="text-secondary small">{{ $session->ip }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ dateFormat($session->created_at) }}</td>
                                <td>{{ $session->last_seen_at ? $session->last_seen_at->diffForHumans() : '--' }}</td>
                                <td>
                                    <form action="{{ route('user.devices.destroy', $session->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger action-confirm">
                                            <i class="ti ti-logout icon"></i>{{ lang('Sign out', 'account') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <div class="empty">
                    <div class="empty-icon"><i class="ti ti-devices icon"></i></div>
                    <p class="empty-title">{{ lang('No devices yet', 'account') }}</p>
                    <p class="empty-subtitle text-secondary">{{ lang('Devices appear here when you sign in to our apps.', 'account') }}</p>
                </div>
            </div>
        @endif
    </div>
@endsection

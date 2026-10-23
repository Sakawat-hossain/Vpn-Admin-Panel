@extends('frontend.user.layouts.app')
@section('title', lang('My Subscription', 'account'))
@section('page-pretitle', lang('Billing', 'account'))
@section('page-title', lang('My Subscription', 'account'))
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card">
                <div class="card-body">
                    @if ($user->isSubscribed() && !$user->subscription->isCancelled())
                        <div class="row g-3 align-items-center">
                            <div class="col-12 col-lg">
                                <div class="h2 mb-2">{{ $user->subscription->plan->name }}
                                    <span class="text-secondary fs-4 fw-normal">({{ formatInterval($user->subscription->plan->interval) }})</span>
                                </div>
                                @if (!$user->subscription->isFree())
                                    <div class="text-secondary">{{ lang('Next bill date', 'account') }} :
                                        <span class="fw-bold {{ $user->subscription->isAboutToExpire() ? 'text-warning' : ($user->subscription->isExpired() ? 'text-danger' : '') }}">
                                            {{ dateFormat($user->subscription->expiry_at) }}
                                        </span>
                                    </div>
                                @else
                                    <div class="text-secondary">
                                        {{ str_replace('{period}', $user->subscription->plan->interval ? lang('month', 'account') : lang('year', 'account'), lang('Your subscription will automatically renew every {period}', 'account')) }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-12 col-lg-auto">
                                <div class="btn-list">
                                    @if ((!$user->subscription->isFree() && $user->subscription->isAboutToExpire()) || $user->subscription->isExpired())
                                        <form action="{{ route('subscribe', [$user->subscription->plan->id, 'renew']) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-primary action-confirm">
                                                <i class="ti ti-refresh icon"></i>{{ lang('Renew', 'account') }}
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('pricing') }}" class="btn btn-primary">
                                        <i class="ti ti-arrow-big-up-lines icon"></i>{{ lang('Upgrade', 'account') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="empty">
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
    </div>
@endsection
